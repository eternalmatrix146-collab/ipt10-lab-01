<?php

declare(strict_types=1);

namespace Phantom\Exceptions;

use Phantom\Http\Request;
use Phantom\Http\Response;
use Phantom\Session\Session;
use Phantom\Support\Logger;
use Phantom\View\View;
use Throwable;

/**
 * Converts any exception into a Response:
 *  - ValidationException -> redirect back with errors (HTML) or 422 JSON
 *  - HttpException       -> errors/{status} view or JSON
 *  - anything else       -> 500, with a detailed debug page when APP_DEBUG=true
 */
class Handler
{
    public function __construct(
        private View $view,
        private Session $session,
        private Logger $logger,
        private bool $debug
    ) {
    }

    public function render(Throwable $e, Request $request): Response
    {
        if ($e instanceof ValidationException) {
            return $this->renderValidation($e, $request);
        }

        $status = $e instanceof HttpException ? $e->getStatus() : 500;
        $headers = $e instanceof HttpException ? $e->getHeaders() : [];

        if ($status >= 500) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile() . ':' . $e->getLine(),
                'path' => $request->path(),
            ]);
        }

        $message = $status >= 500 && !$this->debug ? 'Something went wrong on our side.' : $e->getMessage();

        if ($request->wantsJson()) {
            $payload = ['error' => $message, 'status' => $status];
            if ($this->debug && $status >= 500) {
                $payload['exception'] = $e::class;
                $payload['location'] = $e->getFile() . ':' . $e->getLine();
            }
            $response = Response::json($payload, $status);
        } else {
            $response = Response::html($this->renderHtml($e, $status, $message), $status);
        }

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        return $response;
    }

    private function renderValidation(ValidationException $e, Request $request): Response
    {
        if ($request->wantsJson()) {
            return Response::json(['error' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        $input = $e->input();
        foreach (array_keys($input) as $key) {
            if (str_contains((string) $key, 'password') || $key === '_token') {
                unset($input[$key]);
            }
        }
        $this->session->flash('errors', $e->errors());
        $this->session->flash('old', $input);

        return Response::redirect(back_url($request->fullPath()));
    }

    private function renderHtml(Throwable $e, int $status, string $message): string
    {
        try {
            if ($this->debug && $status >= 500) {
                return $this->view->render('errors.debug', ['exception' => $e, 'status' => $status]);
            }
            $template = $this->view->exists("errors.{$status}") ? "errors.{$status}" : 'errors.generic';
            return $this->view->render($template, ['status' => $status, 'message' => $message]);
        } catch (Throwable) {
            // The error page itself failed (e.g. DB down while building the nav) — fall back to plain HTML.
            $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            $detail = $this->debug
                ? '<pre>' . htmlspecialchars($e::class . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>'
                : '';
            return "<!doctype html><meta charset=utf-8><title>Error {$status}</title>"
                . "<body style=\"font-family:sans-serif;background:#111;color:#fff;padding:2rem\">"
                . "<h1 style=\"color:#e60012\">Error {$status}</h1><p>{$safe}</p>{$detail}</body>";
        }
    }
}
