<?php
// includes/auth.php
// Include this at the top of every protected page.
// It starts the session and sends visitors who are not logged in to login.php.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pages inside admin/ are one folder deeper, so their links need "../" in front.
$base = (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin') ? '../' : '';

if (empty($_SESSION['admin'])) {
    header("Location: {$base}login.php");
    exit;
}

// Save a one-time message, then go to another page.
// (Redirecting after a successful POST stops a browser refresh from saving twice.)
function flash_and_redirect($message, $url) {
    $_SESSION['flash'] = $message;
    header("Location: $url");
    exit;
}
