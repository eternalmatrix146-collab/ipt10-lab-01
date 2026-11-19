<?php

declare(strict_types=1);

namespace Phantom\Middleware;

use Phantom\Http\Request;
use Phantom\Http\Response;

/**
 * Middleware wraps the request handler ("onion" model). Call $next($request)
 * to continue, or return a Response early to short-circuit.
 */
interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
