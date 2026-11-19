<?php

declare(strict_types=1);

namespace Phantom\Middleware;

use Phantom\Http\Request;
use Phantom\Http\Response;
use Phantom\Support\Logger;

/**
 * Logs method, path, status and duration of every request, and adds an
 * X-Response-Time header.
 */
class LogRequests implements MiddlewareInterface
{
    public function __construct(private Logger $logger)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $ms = round((microtime(true) - $start) * 1000, 1);

        $this->logger->info(sprintf('%s %s -> %d (%sms)', $request->method(), $request->path(), $response->status(), $ms), [
            'ip' => $request->ip(),
        ]);

        return $response->withHeader('X-Response-Time', $ms . 'ms');
    }
}
