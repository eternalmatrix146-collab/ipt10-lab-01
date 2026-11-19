<?php

declare(strict_types=1);

namespace Phantom\Middleware;

use Phantom\Http\Request;
use Phantom\Http\Response;
use Phantom\Session\Session;

/**
 * Starts the session and remembers the last GET page, used by back().
 */
class StartSession implements MiddlewareInterface
{
    public function __construct(private Session $session)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $this->session->start();
        $response = $next($request);

        if ($request->isMethod('GET') && !$request->wantsJson() && $response->status() === 200
            && str_contains((string) $response->header('Content-Type'), 'text/html')) {
            $this->session->put('_previous_url', $request->fullPath());
        }
        return $response;
    }
}
