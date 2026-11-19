<?php

declare(strict_types=1);

namespace App\Middleware;

use Phantom\Auth\Auth;
use Phantom\Exceptions\HttpException;
use Phantom\Http\Request;
use Phantom\Http\Response;
use Phantom\Middleware\MiddlewareInterface;
use Phantom\Session\Session;

/**
 * Only logged-in users may pass. Guests are redirected to the login page
 * and sent back to where they were going afterwards.
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private Auth $auth, private Session $session)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->auth->check()) {
            $request->setAttribute('user', $this->auth->user());
            return $next($request);
        }

        if ($request->wantsJson()) {
            throw new HttpException(401, 'Please log in first.');
        }

        if ($request->isMethod('GET')) {
            $this->session->put('_intended', $request->fullPath());
        }
        $this->session->flash('status', ['type' => 'info', 'message' => 'Log in to continue.']);
        return Response::redirect(url('/login'));
    }
}
