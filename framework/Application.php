<?php

declare(strict_types=1);

namespace Phantom;

use Closure;
use Phantom\Database\Connection;
use Phantom\Database\DB;
use Phantom\Exceptions\Handler;
use Phantom\Exceptions\HttpException;
use Phantom\Http\Request;
use Phantom\Http\Response;
use Phantom\Middleware\Pipeline;
use Phantom\Routing\Router;
use Phantom\Session\Session;
use Phantom\Support\Cache;
use Phantom\Support\Config;
use Phantom\Support\Env;
use Phantom\Support\Logger;
use Phantom\View\View;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/**
 * The framework kernel.
 *
 * Request lifecycle:
 *   public/index.php -> Application::handle()
 *     -> global middleware (session, logging, CSRF...)
 *     -> Router::match()  (404 / 405)
 *     -> route middleware (auth, admin, throttle...)
 *     -> controller action (dependencies + route params injected)
 *     -> Response::send()
 */
class Application extends Container
{
    private static ?Application $instance = null;

    private Router $router;

    /** @var array<int, string> middleware run on every request */
    private array $middleware = [];

    /** @var array<string, string> short names for route middleware */
    private array $middlewareAliases = [];

    public function __construct(private string $basePath)
    {
        self::$instance = $this;
        $this->instance(self::class, $this);
        $this->instance(Container::class, $this);

        Env::load($basePath . '/.env');
        date_default_timezone_set((string) Env::get('APP_TIMEZONE', 'Asia/Manila'));

        $config = Config::fromDirectory($basePath . '/config');
        $this->instance(Config::class, $config);

        $this->router = new Router();
        $this->instance(Router::class, $this->router);

        $this->registerCoreServices($config);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Application has not been created yet.');
        }
        return self::$instance;
    }

    private function registerCoreServices(Config $config): void
    {
        $this->singleton(Session::class, fn () => new Session((string) $config->get('app.session_name', 'phantom_session')));
        $this->singleton(View::class, fn () => new View($this->basePath . '/resources/views'));
        $this->singleton(Logger::class, fn () => new Logger($this->basePath . '/storage/logs'));
        $this->singleton(Cache::class, fn () => new Cache($this->basePath . '/storage/cache'));
        $this->singleton(Handler::class, fn (Container $c) => new Handler(
            $c->make(View::class),
            $c->make(Session::class),
            $c->make(Logger::class),
            (bool) $config->get('app.debug', false)
        ));

        // The PDO connection is only opened the first time a query runs.
        DB::setResolver(fn () => Connection::fromConfig((array) $config->get('database')));
        $this->bind(Connection::class, fn () => DB::connection());
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path === '' ? '' : DIRECTORY_SEPARATOR . ltrim($path, '/\\'));
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function config(): Config
    {
        return $this->make(Config::class);
    }

    public function view(): View
    {
        return $this->make(View::class);
    }

    /** @param array<int, string> $middleware */
    public function setMiddleware(array $middleware): void
    {
        $this->middleware = $middleware;
    }

    /** @param array<string, string> $aliases */
    public function setMiddlewareAliases(array $aliases): void
    {
        $this->middlewareAliases = $aliases;
    }

    public function loadRoutes(string $file): void
    {
        $router = $this->router;
        $app = $this;
        require $file;
    }

    public function run(): void
    {
        $request = Request::fromGlobals();
        $this->handle($request)->send();
    }

    public function handle(Request $request): Response
    {
        $this->instance(Request::class, $request);

        try {
            return (new Pipeline($this, $this->middlewareAliases))
                ->through($this->middleware)
                ->then($request, fn (Request $req) => $this->dispatch($req));
        } catch (Throwable $e) {
            return $this->make(Handler::class)->render($e, $request);
        }
    }

    private function dispatch(Request $request): Response
    {
        [$route, $params] = $this->router->match($request);
        $request->setRouteParams($params);

        return (new Pipeline($this, $this->middlewareAliases))
            ->through($route->getMiddleware())
            ->then($request, fn (Request $req) => $this->callHandler($route->getHandler(), $req));
    }

    private function callHandler(mixed $handler, Request $request): Response
    {
        if (is_string($handler) && str_contains($handler, '@')) {
            $handler = explode('@', $handler, 2);
        }

        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = is_object($class) ? $class : $this->make($class);
            if (!method_exists($controller, $method)) {
                throw new HttpException(500, "Action {$class}::{$method} does not exist.");
            }
            $reflection = new ReflectionMethod($controller, $method);
            $result = $reflection->invokeArgs($controller, $this->resolveArguments($reflection->getParameters(), $request));
        } elseif ($handler instanceof Closure) {
            $reflection = new ReflectionFunction($handler);
            $result = $reflection->invokeArgs($this->resolveArguments($reflection->getParameters(), $request));
        } else {
            throw new HttpException(500, 'Invalid route handler.');
        }

        return $this->toResponse($result);
    }

    /**
     * Injects the Request, container services (by type) and route parameters (by name).
     *
     * @param \ReflectionParameter[] $parameters
     * @return array<int, mixed>
     */
    private function resolveArguments(array $parameters, Request $request): array
    {
        $args = [];
        foreach ($parameters as $param) {
            $type = $param->getType();
            $name = $param->getName();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $type->getName() === Request::class ? $request : $this->make($type->getName());
                continue;
            }

            $value = $request->param($name);
            if ($value !== null) {
                $args[] = ($type instanceof ReflectionNamedType && $type->getName() === 'int') ? (int) $value : $value;
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new HttpException(500, "Cannot resolve argument \${$name}.");
            }
        }
        return $args;
    }

    private function toResponse(mixed $result): Response
    {
        return match (true) {
            $result instanceof Response => $result,
            is_array($result), $result instanceof \JsonSerializable => Response::json($result),
            default => Response::html((string) $result),
        };
    }
}
