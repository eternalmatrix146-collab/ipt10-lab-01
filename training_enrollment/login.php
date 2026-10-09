<?php
// login.php -- Log in with the single administrator account (set in config/db.php)
require_once __DIR__ . '/config/db.php';
session_start();

// Already logged in? Go straight to the home page.
if (!empty($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === $admin_user && password_verify($password, $admin_pass_hash)) {
        session_regenerate_id(true);   // new session id after login (safer)
        $_SESSION['admin'] = $username;
        header('Location: index.php');
        exit;
    }
    $error = 'Wrong username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log In - Training Enrollment System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main class="login">
    <h1>Training Enrollment System</h1>
    <h2>Administrator Log In</h2>
    <?php if ($error !== ''): ?>
        <p class="msg error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="post">
        <label>Username
            <input type="text" name="username" required autofocus>
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <button type="submit">Log In</button>
    </form>
</main>
</body>
</html>
