<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\User;
use Phantom\Auth\Auth;
use Phantom\Exceptions\HttpException;
use Phantom\Http\Request;
use Phantom\Http\Response;
use Phantom\Middleware\MiddlewareInterface;

/**
 * Must run after "auth". Blocks non-admin users with 403.
 */
class AdminMiddleware implements MiddlewareInterface
{
    public function __construct(private Auth $auth)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $user = $this->auth->user();
        if (!$user instanceof User || !$user->isAdmin()) {
            throw new HttpException(403, 'This area is for the admin crew only.');
        }
        return $next($request);
    }
}
