<?php
/**
 * Campus Notes JSON API (issues 9, 10, 11).
 *
 *   GET  /api.php              -> the signed-in user's notes
 *   GET  /api.php?user_id=N    -> allowed only when N is the signed-in user
 *   POST /api.php              -> update one of the signed-in user's notes
 *        Content-Type: application/json
 *        X-CSRF-Token: <token from <meta name="csrf-token"> on the main page>
 *        {"id": 1, "title": "...", "body": "..."}
 *
 * Same-origin only: no Access-Control-Allow-* headers are sent, and requests
 * the browser marks as cross-origin are rejected.
 */
declare(strict_types=1);

const APP_JSON_API      = true;
const API_MAX_BODY_BYTES = 65536;

require dirname(__DIR__) . '/src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");

// Issue 11: refuse cross-origin callers before touching the session.
if (!request_origin_is_trusted()) {
    json_response(403, ['error' => 'Cross-origin requests are not allowed']);
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET' && $method !== 'POST') {
    header('Allow: GET, POST');
    json_response(405, ['error' => 'Method not supported']);
}

start_secure_session();

// Issue 9: authentication.
$userId = current_user_id();
if ($userId === null) {
    json_response(401, ['error' => 'Authentication required']);
}

$db = db();

if ($method === 'GET') {
    // Issue 9: per-record authorization. user_id is optional and must be yourself.
    if (isset($_GET['user_id'])) {
        $requested = parse_id($_GET['user_id']);
        if ($requested === null) {
            json_response(400, ['error' => 'user_id must be a positive integer']);
        }
        if ($requested !== $userId) {
            json_response(403, ['error' => 'Forbidden']);
        }
    }
    json_response(200, ['notes' => notes_for_user($db, $userId)]);
}

// ----- POST: update a note ---------------------------------------------------

// Issue 11: token in a custom header. Browsers cannot add custom headers to a
// cross-origin request without a CORS preflight, which this API never approves.
if (!csrf_is_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    json_response(403, ['error' => 'Missing or invalid CSRF token']);
}

// Issue 10: request validation.
$contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
if ($contentType !== 'application/json') {
    json_response(415, ['error' => 'Content-Type must be application/json']);
}

$raw = file_get_contents('php://input', false, null, 0, API_MAX_BODY_BYTES + 1);
if ($raw === false || strlen($raw) > API_MAX_BODY_BYTES) {
    json_response(413, ['error' => 'Request body too large']);
}

try {
    $data = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    json_response(400, ['error' => 'Malformed JSON']);
}

if (!is_array($data) || array_is_list($data)) {
    json_response(400, ['error' => 'Expected a JSON object']);
}

$allowed = ['id', 'title', 'body'];
$unknown = array_diff(array_keys($data), $allowed);
$errors  = [];
if ($unknown) {
    $errors[] = 'Unexpected field(s): ' . implode(', ', array_map('strval', $unknown));
}
$noteId = (isset($data['id']) && is_int($data['id']) && $data['id'] > 0) ? $data['id'] : null;
if ($noteId === null) {
    $errors[] = 'id must be a positive integer.';
}
$errors = array_merge($errors, validate_note_input($data['title'] ?? null, $data['body'] ?? null));
if ($errors) {
    json_response(422, ['error' => 'Validation failed', 'details' => $errors]);
}

// Issue 9: ownership enforced in SQL. Another user's note looks like a missing note.
if (!update_note_for_user($db, $noteId, $userId, $data['title'], $data['body'])) {
    json_response(404, ['error' => 'Note not found']);
}

json_response(200, ['updated' => 1]);
