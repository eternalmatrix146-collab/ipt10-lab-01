<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/helpers.php';

use Bramus\Router\Router;
use App\Controllers\DiscussionController;
use App\Controllers\AuthController;
use App\Controllers\ReplyController;

// --- Session & Cookie Security ---
if (session_status() === PHP_SESSION_NONE) {
    $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// --- Router Setup ---
$router = new Router();

// --- Home route: redirect to first discussion ---
$router->get('/', function () {
    redirect('/discussions/1');
});

// --- GET: view discussion with nested comment tree ---
$router->get('/discussions/{id}', 'App\Controllers\DiscussionController@show');

// --- Auth routes ---
$router->get('/register', 'App\Controllers\AuthController@showRegister');
$router->post('/register', 'App\Controllers\AuthController@register');
$router->get('/login', 'App\Controllers\AuthController@showLogin');
$router->post('/login', 'App\Controllers\AuthController@login');
$router->post('/logout', 'App\Controllers\AuthController@logout');

// --- Reply routes ---
$router->post('/replies', 'App\Controllers\ReplyController@store');

// --- 404 Handler ---
$router->set404(function () {
    http_response_code(404);
    $mustache = new \Mustache_Engine([
        'loader' => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/../app/Views'),
        'escape' => function ($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        },
    ]);
    echo $mustache->render('error', [
        'status_code' => 404,
        'error_message' => 'The page you requested could not be found.',
        'site_title' => '404 Not Found — Nested Forum',
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'csrf_token' => csrf_token(),
    ]);
});

$router->run();
