<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap: autoloading, the application container, services and routes.
| Shared by public/index.php (web) and ./phantom (CLI).
|--------------------------------------------------------------------------
*/

use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\ShareViewData;
use App\Models\User;
use App\Services\AI\KnowledgeBase;
use App\Services\AI\NimClient;
use App\Services\CartService;
use App\Services\CurrencyService;
use Phantom\Application;
use Phantom\Auth\Auth;
use Phantom\Autoloader;
use Phantom\Http\Client\HttpClient;
use Phantom\Middleware\LogRequests;
use Phantom\Middleware\SecurityHeaders;
use Phantom\Middleware\StartSession;
use Phantom\Middleware\ThrottleRequests;
use Phantom\Middleware\VerifyCsrfToken;
use Phantom\Session\Session;
use Phantom\Support\Logger;

$basePath = dirname(__DIR__);

require $basePath . '/framework/Autoloader.php';

(new Autoloader())
    ->addNamespace('Phantom', $basePath . '/framework')
    ->addNamespace('App', $basePath . '/app')
    ->addNamespace('Database', $basePath . '/database')
    ->addNamespace('Tests', $basePath . '/tests')
    ->register();

require $basePath . '/framework/helpers.php';
require $basePath . '/app/helpers.php';

$app = new Application($basePath);

/*
| Error reporting: show everything in debug mode, nothing to the browser otherwise.
*/
error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

/*
| Services
*/
$app->singleton(Auth::class, fn ($c) => new Auth($c->make(Session::class), User::class));
$app->singleton(HttpClient::class, fn () => new HttpClient());
$app->singleton(NimClient::class, fn ($c) => new NimClient(
    $c->make(HttpClient::class),
    (array) config('services.nvidia')
));
$app->singleton(KnowledgeBase::class, fn () => new KnowledgeBase($app->basePath('resources/knowledge/faq.md')));
// One instance per request: rates are fetched once and the cart token is read once.
$app->singleton(CurrencyService::class, fn ($c) => new CurrencyService($c->make(HttpClient::class), $c->make(Logger::class)));
$app->singleton(CartService::class, fn ($c) => new CartService($c->make(Session::class), $c->make(Auth::class)));

/*
| Middleware — global stack runs on every request, aliases are used per route.
*/
$app->setMiddleware([
    SecurityHeaders::class,
    LogRequests::class,
    StartSession::class,
    VerifyCsrfToken::class,
    ShareViewData::class,
]);

$app->setMiddlewareAliases([
    'auth' => AuthMiddleware::class,
    'admin' => AdminMiddleware::class,
    'guest' => GuestMiddleware::class,
    'throttle' => ThrottleRequests::class,
]);

$app->loadRoutes($basePath . '/routes/web.php');

return $app;
