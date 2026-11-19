<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Front controller — every web request enters here.
|--------------------------------------------------------------------------
*/

// When served by `php -S`, let the built-in server deliver real static files.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
}

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->run();
