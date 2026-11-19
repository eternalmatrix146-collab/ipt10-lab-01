<?php

declare(strict_types=1);

namespace Phantom\Http;

use Phantom\Application;
use Phantom\Validation\Validator;

/**
 * Base controller with shortcuts for the common response types.
 */
abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return Response::html(Application::getInstance()->view()->render($template, $data), $status);
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect(url($path));
    }

    /** @param array<string, string|int> $params */
    protected function redirectRoute(string $name, array $params = []): Response
    {
        return Response::redirect(route($name, $params));
    }

    /** Redirects to the previous page (Referer) or a fallback path. */
    protected function back(string $fallback = '/'): Response
    {
        return Response::redirect(back_url($fallback));
    }

    /**
     * @param array<string, string|string[]> $rules
     * @param array<string, string> $messages
     * @return array<string, mixed>
     */
    protected function validate(Request $request, array $rules, array $messages = []): array
    {
        return Validator::make($request->all(), $rules, $messages)->validate();
    }

    protected function flash(string $type, string $message): void
    {
        session()->flash('status', ['type' => $type, 'message' => $message]);
    }
}
