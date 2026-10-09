<?php
// logout.php -- End the administrator session
session_start();
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
