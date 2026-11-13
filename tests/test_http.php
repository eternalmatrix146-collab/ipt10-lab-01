<?php
declare(strict_types=1);

/**
 * End-to-End HTTP tests against running web server.
 */

$baseUrl = 'http://127.0.0.1:8000';

function httpGet(string $url): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);

    return [
        'status' => $statusCode,
        'headers' => $headers,
        'body' => $body,
    ];
}

echo "Testing HTTP routes on {$baseUrl}...\n";

// 1. Root redirect
$resRoot = httpGet($baseUrl . '/');
echo "1. GET / -> Status: {$resRoot['status']} (Expected 302)\n";
assert($resRoot['status'] === 302);
assert(str_contains($resRoot['headers'], 'Location: /discussions/1'));

// 2. View discussion 1
$resDisc = httpGet($baseUrl . '/discussions/1');
echo "2. GET /discussions/1 -> Status: {$resDisc['status']} (Expected 200)\n";
assert($resDisc['status'] === 200);
assert(str_contains($resDisc['body'], 'How do I learn PHP?'));
assert(str_contains($resDisc['body'], 'Start with the PHP manual.'));

// 3. Malformed discussion ID
$resMalformed = httpGet($baseUrl . '/discussions/abc');
echo "3. GET /discussions/abc -> Status: {$resMalformed['status']} (Expected 404)\n";
assert($resMalformed['status'] === 404);

// 4. Missing discussion ID
$resMissing = httpGet($baseUrl . '/discussions/9999');
echo "4. GET /discussions/9999 -> Status: {$resMissing['status']} (Expected 404)\n";
assert($resMissing['status'] === 404);

// 5. Login page
$resLogin = httpGet($baseUrl . '/login');
echo "5. GET /login -> Status: {$resLogin['status']} (Expected 200)\n";
assert($resLogin['status'] === 200);
assert(str_contains($resLogin['body'], 'Log in to your account'));

// 6. Register page
$resReg = httpGet($baseUrl . '/register');
echo "6. GET /register -> Status: {$resReg['status']} (Expected 200)\n";
assert($resReg['status'] === 200);
assert(str_contains($resReg['body'], 'Create an account'));

// 7. Static CSS
$resCss = httpGet($baseUrl . '/css/style.css');
echo "7. GET /css/style.css -> Status: {$resCss['status']} (Expected 200)\n";
assert($resCss['status'] === 200);

// 8. Static JS
$resJs = httpGet($baseUrl . '/js/forum.js');
echo "8. GET /js/forum.js -> Status: {$resJs['status']} (Expected 200)\n";
assert($resJs['status'] === 200);

echo "\nAll HTTP tests passed successfully!\n";
