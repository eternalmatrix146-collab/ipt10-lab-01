<?php
/**
 * Shared start-up for every entry point (public/index.php, public/api.php).
 *
 * Responsibilities kept here:
 *   - safe error reporting (log, never display)       -> issue 8
 *   - one generic handler for uncaught exceptions      -> issue 8
 *   - loading the helper files in src/
 */
declare(strict_types=1);

// Issue 8: never print errors, warnings or stack traces to the browser.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
// Keep function arguments (e.g. the DB password passed to new PDO()) out of traces.
ini_set('zend.exception_ignore_args', '1');
error_reporting(E_ALL);

require __DIR__ . '/view.php';
require __DIR__ . '/database.php';
require __DIR__ . '/session.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/users.php';
require __DIR__ . '/notes.php';

// Turn warnings/notices into exceptions so bad input fails closed instead of
// continuing with half-valid data.
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
        return false;   // log only (default handler), do not break the request
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Log a sanitized diagnostic, show the user a generic message.
set_exception_handler(static function (Throwable $e): void {
    error_log(sprintf(
        '[campus-notes] %s (code %s) at %s:%d',
        get_class($e),
        (string) $e->getCode(),
        basename($e->getFile()),
        $e->getLine()
    ));

    if (!headers_sent()) {
        http_response_code(500);
    }
    if (defined('APP_JSON_API')) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['error' => 'Internal server error']);
    } else {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<!doctype html><meta charset="utf-8"><title>Error</title>'
           . '<p>Something went wrong. Please try again later.</p>';
    }
});
