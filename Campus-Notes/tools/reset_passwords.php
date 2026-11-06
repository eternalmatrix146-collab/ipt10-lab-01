<?php
/**
 * CLI only: re-hash the two synthetic lab accounts with password_hash().
 * Use this if your database still has the starter's MD5 hashes.
 *
 *   php tools/reset_passwords.php
 *
 * Passwords default to the public throwaway values from the README; override
 * with LAB_ALEX_PASSWORD / LAB_SAM_PASSWORD if you chose your own.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/bootstrap.php';

$accounts = [
    'alex@example.test' => getenv('LAB_ALEX_PASSWORD') ?: 'LabOnly-Alex-123',
    'sam@example.test'  => getenv('LAB_SAM_PASSWORD')  ?: 'LabOnly-Sam-456',
];

$stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
foreach ($accounts as $email => $password) {
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $email]);
    echo $email . ': ' . ($stmt->rowCount() === 1 ? 'password re-hashed' : 'account not found') . PHP_EOL;
}
