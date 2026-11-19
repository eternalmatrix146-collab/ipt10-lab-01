<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AI\NimClient;
use Phantom\Auth\Auth;
use Phantom\Http\Request;
use Phantom\Http\Response;
use Phantom\Middleware\MiddlewareInterface;
use Phantom\View\View;

/**
 * Makes values every layout needs (current user, request path, AI mode)
 * available to all templates.
 */
class ShareViewData implements MiddlewareInterface
{
    public function __construct(private View $view, private Auth $auth, private NimClient $nim)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $this->view->share('authUser', $this->auth->user());
        $this->view->share('currentPath', $request->path());
        $this->view->share('aiMode', $this->nim->isConfigured() ? 'nim' : 'offline');
        return $next($request);
    }
}
