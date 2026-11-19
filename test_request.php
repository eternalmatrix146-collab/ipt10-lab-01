<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
$_SERVER['REQUEST_URI'] = '/discussions/1';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SERVER_PORT'] = '80';
require __DIR__ . '/public/index.php';
