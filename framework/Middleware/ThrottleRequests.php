<?php

declare(strict_types=1);

namespace Phantom\Middleware;

use Phantom\Application;
use Phantom\Exceptions\HttpException;
use Phantom\Http\Request;
use Phantom\Http\Response;
use Phantom\Support\Cache;

/**
 * Fixed-window rate limiter keyed by client IP + route.
 * Not keyed by session: a client can drop its cookie to get a fresh session,
 * which would reset the counter and defeat the limit.
 * Use as route middleware: "throttle:20,60" = 20 requests per 60 seconds.
 */
class ThrottleRequests implements MiddlewareInterface
{
    private int $maxAttempts;

    private int $decaySeconds;

    public function __construct(int|string $maxAttempts = 60, int|string $decaySeconds = 60)
    {
        $this->maxAttempts = max(1, (int) $maxAttempts);
        $this->decaySeconds = max(1, (int) $decaySeconds);
    }

    public function handle(Request $request, callable $next): Response
    {
        $app = Application::getInstance();
        $cache = $app->make(Cache::class);

        $window = intdiv(time(), $this->decaySeconds);
        $key = 'throttle:' . sha1($request->ip() . '|' . $request->path()) . ':' . $window;

        $hits = (int) $cache->get($key, 0) + 1;
        $cache->put($key, $hits, $this->decaySeconds);

        $retryAfter = $this->decaySeconds - (time() % $this->decaySeconds);
        if ($hits > $this->maxAttempts) {
            throw new HttpException(429, "Too many requests. Try again in {$retryAfter} seconds.", [
                'Retry-After' => (string) $retryAfter,
            ]);
        }

        return $next($request)
            ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $this->maxAttempts - $hits));
    }
}
