<?php

declare(strict_types=1);

namespace App\Middleware;

use Phantom\Auth\Auth;
use Phantom\Http\Request;
use Phantom\Http\Response;
use Phantom\Middleware\MiddlewareInterface;

/**
 * Keeps logged-in users away from the login/register pages.
 */
class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(private Auth $auth)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->auth->check()) {
            return Response::redirect(url('/'));
        }
        return $next($request);
    }
}
