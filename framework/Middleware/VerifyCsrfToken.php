<?php

declare(strict_types=1);

namespace Phantom\Middleware;

use Phantom\Exceptions\HttpException;
use Phantom\Http\Request;
use Phantom\Http\Response;
use Phantom\Session\Session;

/**
 * Rejects state-changing requests that do not carry the session's CSRF token,
 * either as a "_token" form field or an "X-CSRF-TOKEN" header (fetch calls).
 */
class VerifyCsrfToken implements MiddlewareInterface
{
    public function __construct(private Session $session)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $token = (string) ($request->input('_token') ?? $request->header('x-csrf-token') ?? '');
        if ($token === '' || !hash_equals($this->session->token(), $token)) {
            throw new HttpException(419, 'Page expired — your security token is missing or stale. Refresh and try again.');
        }

        return $next($request);
    }
}
