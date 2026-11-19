<?php

declare(strict_types=1);

use Phantom\Application;
use Phantom\Auth\Auth;
use Phantom\Http\Request;
use Phantom\Http\Response;
use Phantom\Session\Session;
use Phantom\Support\Env;

/*
|--------------------------------------------------------------------------
| Global helper functions available to controllers and views.
|--------------------------------------------------------------------------
*/

function app(?string $id = null): mixed
{
    $app = Application::getInstance();
    return $id === null ? $app : $app->make($id);
}

function env(string $key, mixed $default = null): mixed
{
    return Env::get($key, $default);
}

function config(string $key, mixed $default = null): mixed
{
    return Application::getInstance()->config()->get($key, $default);
}

/** @param array<string, mixed> $data */
function view(string $template, array $data = [], int $status = 200): Response
{
    return Response::html(Application::getInstance()->view()->render($template, $data), $status);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path_prefix(): string
{
    $app = Application::getInstance();
    return $app->has(Request::class) ? $app->make(Request::class)->basePath() : '';
}

/** Absolute-path URL for an internal path, respecting a sub-folder install. */
function url(string $path = '/'): string
{
    if (preg_match('#^https?://#', $path)) {
        return $path;
    }
    return base_path_prefix() . '/' . ltrim($path, '/');
}

/** URL for a file in /public with a cache-busting version. */
function asset(string $path): string
{
    $file = app()->basePath('public/' . ltrim($path, '/'));
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return url($path) . $version;
}

/** @param array<string, string|int> $params */
function route(string $name, array $params = []): string
{
    return url(Application::getInstance()->router()->url($name, $params));
}

function session(): Session
{
    return app(Session::class);
}

function auth(): Auth
{
    return app(Auth::class);
}

function csrf_token(): string
{
    return session()->token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
}

/** Previously submitted form value (after a validation failure). */
function old(string $key, mixed $default = ''): mixed
{
    $old = session()->getFlash('old', []);
    return $old[$key] ?? $default;
}

/** First validation error for a field, or null. */
function error(string $field): ?string
{
    $errors = session()->getFlash('errors', []);
    return $errors[$field][0] ?? null;
}

function back_url(string $fallback = '/'): string
{
    $app = Application::getInstance();
    $referer = $app->has(Request::class) ? $app->make(Request::class)->header('referer') : null;
    if ($referer !== null) {
        $host = parse_url($referer, PHP_URL_HOST);
        $current = $_SERVER['HTTP_HOST'] ?? null;
        // Only follow same-host referers to avoid open redirects.
        if ($host === null || $current === null || strcasecmp($host, explode(':', $current)[0]) === 0) {
            // Collapse leading slashes/backslashes: "//evil.example" would be a protocol-relative redirect.
            $path = '/' . ltrim((string) parse_url($referer, PHP_URL_PATH), '/\\');
            $query = parse_url($referer, PHP_URL_QUERY);
            return $path . ($query ? '?' . $query : '');
        }
    }
    $previous = session()->get('_previous_url');
    return is_string($previous) ? $previous : url($fallback);
}

function redirect(string $path): Response
{
    return Response::redirect(url($path));
}

function class_basename(string|object $class): string
{
    $class = is_object($class) ? $class::class : $class;
    return basename(str_replace('\\', '/', $class));
}

function str_slug(string $text): string
{
    $text = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
    return $text === '' ? 'item' : $text;
}

function str_limit(string $text, int $limit = 100, string $end = '…'): string
{
    return mb_strlen($text) <= $limit ? $text : rtrim(mb_substr($text, 0, $limit)) . $end;
}
