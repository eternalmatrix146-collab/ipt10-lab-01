<?php
/**
 * Black-box security checks for the repaired Campus Notes app.
 *
 * Run against a FRESHLY IMPORTED schema.sql (the tests change data):
 *
 *   php tests/run_tests.php http://127.0.0.1:8000
 *
 * Optional second URL: a second server started with WRONG database
 * credentials, used to check that DB errors are not shown to users:
 *
 *   php tests/run_tests.php http://127.0.0.1:8000 http://127.0.0.1:8001
 *
 * Needs LAB_DB_USER / LAB_DB_PASSWORD (same as the app) to verify the
 * database directly. Prints a Markdown table and writes tests/results.md.
 * Never prints passwords, session IDs or CSRF tokens.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit;
}

$base       = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');
$brokenBase = isset($argv[2]) ? rtrim($argv[2], '/') : null;
$evil       = 'http://evil.example';

$ALEX = ['email' => 'alex@example.test', 'password' => getenv('LAB_ALEX_PASSWORD') ?: 'LabOnly-Alex-123'];
$SAM  = ['email' => 'sam@example.test',  'password' => getenv('LAB_SAM_PASSWORD')  ?: 'LabOnly-Sam-456'];

// ---------------------------------------------------------------------------
final class Client
{
    /** @var array<string,string> */
    public array $cookies = [];

    public function __construct(private string $base) {}

    /** @return array{status:int, headers:array<string,string[]>, body:string} */
    public function request(string $method, string $path, array $headers = [], ?string $body = null): array
    {
        if ($this->cookies) {
            $pairs = [];
            foreach ($this->cookies as $k => $v) {
                $pairs[] = $k . '=' . $v;
            }
            $headers[] = 'Cookie: ' . implode('; ', $pairs);
        }
        $ctx = stream_context_create(['http' => [
            'method'          => $method,
            'header'          => implode("\r\n", $headers),
            'content'         => $body ?? '',
            'ignore_errors'   => true,
            'follow_location' => 0,
            'timeout'         => 15,
        ]]);
        $respBody = @file_get_contents($this->base . $path, false, $ctx);
        $raw = function_exists('http_get_last_response_headers')
            ? (http_get_last_response_headers() ?? [])
            : ($http_response_header ?? []);

        $status = 0;
        $out = [];
        foreach ($raw as $i => $line) {
            if ($i === 0 && preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                $status = (int) $m[1];
                continue;
            }
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $out[strtolower(trim($parts[0]))][] = trim($parts[1]);
            }
        }
        foreach ($out['set-cookie'] ?? [] as $sc) {
            $first = explode(';', $sc, 2)[0];
            [$name, $value] = array_pad(explode('=', $first, 2), 2, '');
            $expired = preg_match('/expires=([^;]+)/i', $sc, $m) && strtotime($m[1]) < time();
            if ($value === '' || $value === 'deleted' || $expired) {
                unset($this->cookies[$name]);
            } else {
                $this->cookies[$name] = $value;
            }
        }
        return ['status' => $status, 'headers' => $out, 'body' => $respBody === false ? '' : $respBody];
    }

    public function get(string $path, array $headers = []): array
    {
        return $this->request('GET', $path, $headers);
    }

    /** Same-origin form POST, like a browser submitting the app's own form. */
    public function postForm(array $fields, ?string $origin = null): array
    {
        return $this->request('POST', '/', [
            'Content-Type: application/x-www-form-urlencoded',
            'Origin: ' . ($origin ?? $this->base),
        ], http_build_query($fields));
    }

    public function csrf(): string
    {
        $r = $this->get('/');
        return preg_match('/<meta name="csrf-token" content="([^"]+)"/', $r['body'], $m) ? html_entity_decode($m[1]) : '';
    }

    public function login(array $account): array
    {
        $token = $this->csrf();
        return $this->postForm(['csrf_token' => $token, 'action' => 'login'] + $account);
    }

    public function api(string $method, string $path, array $headers = [], ?string $body = null): array
    {
        return $this->request($method, '/api.php' . $path, $headers, $body);
    }
}

// ---------------------------------------------------------------------------
$results = [];
function check(string $id, string $area, string $what, string $expected, bool $pass, string $actual): void
{
    global $results;
    $results[] = [$id, $area, $what, $expected, $actual, $pass ? 'PASS' : 'FAIL'];
}
function not_run(string $id, string $area, string $what, string $expected, string $reason): void
{
    global $results;
    $results[] = [$id, $area, $what, $expected, $reason, 'NOT RUN'];
}
function header_value(array $r, string $name): string
{
    return implode(', ', $r['headers'][strtolower($name)] ?? []);
}
function session_cookie_name(Client $c): ?string
{
    foreach (array_keys($c->cookies) as $k) {
        if (str_contains($k, 'campus_notes')) {
            return $k;
        }
    }
    return null;
}

$user = getenv('LAB_DB_USER');
$pass = getenv('LAB_DB_PASSWORD');
if (!$user || !$pass) {
    fwrite(STDERR, "Set LAB_DB_USER and LAB_DB_PASSWORD first.\n");
    exit(2);
}
try {
    $pdo = new PDO('mysql:host=' . (getenv('LAB_DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('LAB_DB_NAME') ?: 'notes_lab') . ';charset=utf8mb4',
        $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    $hint = match ((int) $e->getCode()) {
        1045    => 'Access denied: LAB_DB_USER / LAB_DB_PASSWORD do not match the MySQL account.',
        1049    => 'Database notes_lab does not exist: import schema.sql first.',
        2002    => 'Cannot reach MySQL: start it in the XAMPP Control Panel.',
        default => 'Check that MySQL is running and the credentials are correct.',
    };
    fwrite(STDERR, 'Cannot connect to the database (code ' . $e->getCode() . "). {$hint}\n");
    exit(2);
}
$note = static function (int $id) use ($pdo): ?array {
    $s = $pdo->prepare('SELECT id, user_id, title, body FROM notes WHERE id = ?');
    $s->execute([$id]);
    return $s->fetch() ?: null;
};

$sam3 = $note(3);
if (!$sam3 || (int) $sam3['user_id'] !== 2 || !$note(1)) {
    fwrite(STDERR, "Expected the fresh seed data (notes 1-2 for Alex, 3-4 for Sam). Re-import schema.sql and retry.\n");
    exit(2);
}

// =========================== Login, passwords, session =====================
$alex = new Client($base);
$alex->get('/');
$preLoginSid = $alex->cookies[session_cookie_name($alex) ?? ''] ?? '';
$r = $alex->login($ALEX);
$page = $alex->get('/');
check('L1', 'Login', 'Valid login (Alex)', '303 redirect, page shows "Signed in as alex"',
    $r['status'] === 303 && str_contains($page['body'], 'Signed in as alex@example.test'),
    "HTTP {$r['status']}; signed-in text " . (str_contains($page['body'], 'Signed in as alex@example.test') ? 'shown' : 'missing'));

$postLoginSid = $alex->cookies[session_cookie_name($alex) ?? ''] ?? '';
check('L2', 'Session', 'Session ID changes at login (fixation)', 'Different session ID before/after login',
    $preLoginSid !== '' && $postLoginSid !== '' && $preLoginSid !== $postLoginSid,
    $preLoginSid !== $postLoginSid ? 'ID changed (values not printed)' : 'ID unchanged');

$bad = new Client($base);
$r = $bad->login(['email' => 'alex@example.test', 'password' => 'wrong-password-xyz']);
$page = $bad->get('/');
check('L3', 'Login', 'Wrong password', 'Not signed in; generic error',
    !str_contains($page['body'], 'Signed in as') && str_contains($page['body'], 'Invalid e-mail or password.'),
    str_contains($page['body'], 'Signed in as') ? 'SIGNED IN' : 'Not signed in; generic message shown');

$sqli = new Client($base);
$sqli->login(['email' => "' OR '1'='1' -- ", 'password' => 'x']);
$sqli->login(['email' => "alex@example.test' -- ", 'password' => 'x']);
$page = $sqli->get('/');
check('L4', 'SQL injection', "Login with ' OR '1'='1' -- and alex@example.test' -- ", 'Not signed in',
    !str_contains($page['body'], 'Signed in as'),
    str_contains($page['body'], 'Signed in as') ? 'SIGNED IN (injection worked)' : 'Not signed in');

$hashes = $pdo->query('SELECT password_hash FROM users')->fetchAll(PDO::FETCH_COLUMN);
$allModern = $hashes && array_reduce($hashes, fn($ok, $h) => $ok && password_get_info($h)['algo'] !== null && !preg_match('/^[a-f0-9]{32}$/i', $h), true);
check('L5', 'Passwords', 'Stored hashes use password_hash()', 'No MD5; all bcrypt/argon2',
    $allModern, count($hashes) . ' hashes checked; ' . ($allModern ? 'all password_hash() format' : 'MD5 or unknown found'));

$reg = new Client($base);
$t = $reg->csrf();
$newEmail = 'mallory' . bin2hex(random_bytes(3)) . '@example.test';
$reg->postForm(['csrf_token' => $t, 'action' => 'register', 'email' => $newEmail,
    'password' => 'Synthetic-Pass-' . bin2hex(random_bytes(4)), 'role' => 'admin']);
$s = $pdo->prepare('SELECT role, password_hash FROM users WHERE email = ?');
$s->execute([$newEmail]);
$row = $s->fetch();
check('L6', 'Role', 'Register while posting role=admin', 'Account created with role student',
    $row && $row['role'] === 'student' && str_starts_with($row['password_hash'], '$2y$'),
    $row ? "role={$row['role']}, hash=" . substr($row['password_hash'], 0, 4) . '...' : 'account not created');

$t = $reg->csrf();
$shortEmail = 'short' . bin2hex(random_bytes(3)) . '@example.test';
$reg->postForm(['csrf_token' => $t, 'action' => 'register', 'email' => $shortEmail, 'password' => 'short']);
$s->execute([$shortEmail]);
check('L7', 'Passwords', 'Register with 5-char password', 'Rejected, no account', !$s->fetch(),
    'account ' . ($s->rowCount() ? 'created' : 'not created'));

// cookie flags
$fresh = new Client($base);
$r = $fresh->get('/');
$sc = header_value($r, 'set-cookie');
check('L8', 'Session', 'Session cookie flags', 'HttpOnly; SameSite=Strict',
    stripos($sc, 'httponly') !== false && stripos($sc, 'samesite=strict') !== false,
    (stripos($sc, 'httponly') !== false ? 'HttpOnly ' : 'no HttpOnly ') . (stripos($sc, 'samesite=strict') !== false ? 'SameSite=Strict' : 'no SameSite=Strict'));

// =========================== Authorization (web) ===========================
$page = $alex->get('/');
check('A1', 'Authorization', "Alex's note list", "Only Alex's notes, none of Sam's",
    str_contains($page['body'], 'Alex: course reminder') && !str_contains($page['body'], 'Sam: study plan'),
    str_contains($page['body'], 'Sam: ') ? "Sam's notes visible" : "Only Alex's notes listed");

$sam = new Client($base);
$sam->login($SAM);
$page = $sam->get('/');
check('A2', 'Authorization', "Sam's note list (second session)", "Only Sam's notes",
    str_contains($page['body'], 'Sam: study plan') && !str_contains($page['body'], 'Alex: '),
    str_contains($page['body'], 'Alex: ') ? "Alex's notes visible" : "Only Sam's notes listed");

$r = $alex->get('/?edit=3');
check('A3', 'Authorization', "Alex opens ?edit=3 (Sam's note)", '404, Sam\'s text not shown',
    $r['status'] === 404 && !str_contains($r['body'], 'Compare authentication'),
    "HTTP {$r['status']}; Sam's text " . (str_contains($r['body'], 'Compare authentication') ? 'SHOWN' : 'not shown'));

$t = $alex->csrf();
$r = $alex->postForm(['csrf_token' => $t, 'action' => 'update', 'id' => '3', 'title' => 'hacked', 'body' => 'hacked']);
$after = $note(3);
check('A4', 'Authorization', "Alex POSTs update for note 3 (Sam's) with valid token", '404, note unchanged',
    $r['status'] === 404 && $after['title'] === $sam3['title'],
    "HTTP {$r['status']}; note 3 " . ($after['title'] === $sam3['title'] ? 'unchanged' : 'CHANGED'));

$t = $alex->csrf();
$r = $alex->postForm(['csrf_token' => $t, 'action' => 'delete', 'id' => '3']);
check('A5', 'Authorization', "Alex POSTs delete for note 3 (Sam's) with valid token", '404, note still exists',
    $r['status'] === 404 && $note(3) !== null, "HTTP {$r['status']}; note 3 " . ($note(3) ? 'exists' : 'DELETED'));

$t = $alex->csrf();
$title = 'owner-test-' . bin2hex(random_bytes(3));
$alex->postForm(['csrf_token' => $t, 'action' => 'save', 'user_id' => '2', 'title' => $title, 'body' => 'x']);
$s = $pdo->prepare('SELECT user_id FROM notes WHERE title = ?');
$s->execute([$title]);
$owner = $s->fetchColumn();
check('A6', 'Owner', 'Alex creates note while posting user_id=2', 'Note owned by Alex (1)',
    (string) $owner === '1', $owner === false ? 'note not created' : "owner user_id={$owner}");

$t = $alex->csrf();
$r = $alex->postForm(['csrf_token' => $t, 'action' => 'update', 'id' => '1', 'title' => 'Alex: course reminder', 'body' => 'Updated by Alex.']);
check('A7', 'CRUD', 'Alex updates own note 1', 'Update succeeds',
    $r['status'] === 303 && ($note(1)['body'] ?? '') === 'Updated by Alex.', "HTTP {$r['status']}; body " . (($note(1)['body'] ?? '') === 'Updated by Alex.' ? 'updated' : 'not updated'));

$t = $alex->csrf();
$r = $alex->postForm(['csrf_token' => $t, 'action' => 'delete', 'id' => '2']);
check('A8', 'CRUD', 'Alex deletes own note 2', 'Delete succeeds',
    $r['status'] === 303 && $note(2) === null, "HTTP {$r['status']}; note 2 " . ($note(2) ? 'still exists' : 'deleted'));

// =========================== XSS ===========================================
$payload = '<script>alert(1)</script>';
$t = $alex->csrf();
$alex->postForm(['csrf_token' => $t, 'action' => 'save', 'title' => 'xss ' . $payload, 'body' => '<img src=x onerror=alert(2)>']);
$page = $alex->get('/');
check('X1', 'XSS', 'Note with <script> title and <img onerror> body', 'Rendered as escaped text',
    !str_contains($page['body'], $payload) && str_contains($page['body'], '&lt;script&gt;alert(1)&lt;/script&gt;')
        && !str_contains($page['body'], '<img src=x'),
    (str_contains($page['body'], $payload) || str_contains($page['body'], '<img src=x')) ? 'RAW HTML in page' : 'escaped (&lt;script&gt;...)');

$id = (int) $pdo->query("SELECT MAX(id) FROM notes WHERE user_id = 1")->fetchColumn();
$t = $alex->csrf();
$alex->postForm(['csrf_token' => $t, 'action' => 'update', 'id' => (string) $id, 'title' => '"><script>alert(3)</script>', 'body' => '</textarea><script>alert(4)</script>']);
$page = $alex->get('/?edit=' . $id);
check('X2', 'XSS', 'Edit form with "> and </textarea> breakout payloads', 'Attribute/textarea stay escaped',
    !str_contains($page['body'], '<script>alert(3)') && !str_contains($page['body'], '<script>alert(4)'),
    (str_contains($page['body'], '<script>alert(3)') || str_contains($page['body'], '<script>alert(4)')) ? 'BREAKOUT' : 'escaped');

$r = $alex->get('/?message=' . rawurlencode($payload));
check('X3', 'XSS', 'GET /?message=<script>…', 'Not reflected', !str_contains($r['body'], $payload) && !str_contains($r['body'], 'alert(1)</'),
    str_contains($r['body'], $payload) ? 'REFLECTED' : 'not reflected');

$r = $bad->login(['email' => '<b>x</b>@example.test', 'password' => 'x']);
$page = $bad->get('/');
check('X4', 'XSS', 'Failed login with HTML in e-mail', 'E-mail not echoed', !str_contains($page['body'], '<b>x</b>'),
    str_contains($page['body'], '<b>x</b>') ? 'ECHOED raw' : 'not echoed');

check('X5', 'XSS', 'Content-Security-Policy header', "Present with script-src 'self'",
    str_contains(header_value($page, 'content-security-policy'), "script-src 'self'"),
    header_value($page, 'content-security-policy') !== '' ? 'present' : 'missing');

// =========================== CSRF (web) ====================================
$count = static fn() => (int) $pdo->query('SELECT COUNT(*) FROM notes')->fetchColumn();
$before = $count();
$r = $alex->postForm(['action' => 'save', 'title' => 'csrf-none', 'body' => 'x']);
check('C1', 'CSRF', 'Save without CSRF token', '403, nothing saved', $r['status'] === 403 && $count() === $before,
    "HTTP {$r['status']}; notes " . ($count() === $before ? 'unchanged' : 'CHANGED'));

$r = $alex->postForm(['csrf_token' => str_repeat('a', 64), 'action' => 'save', 'title' => 'csrf-wrong', 'body' => 'x']);
check('C2', 'CSRF', 'Save with wrong CSRF token', '403, nothing saved', $r['status'] === 403 && $count() === $before,
    "HTTP {$r['status']}; notes " . ($count() === $before ? 'unchanged' : 'CHANGED'));

$t = $alex->csrf();
$r = $alex->postForm(['csrf_token' => $t, 'action' => 'save', 'title' => 'csrf-origin', 'body' => 'x'], $evil);
check('C3', 'CSRF', 'Valid token but Origin: http://evil.example', '403, nothing saved', $r['status'] === 403 && $count() === $before,
    "HTTP {$r['status']}; notes " . ($count() === $before ? 'unchanged' : 'CHANGED'));

$alex->get('/?delete=1');
check('C4', 'CSRF', 'Old GET link /?delete=1', 'Ignored, note 1 still exists', $note(1) !== null, $note(1) ? 'note 1 exists' : 'DELETED');

$alex->get('/?logout=1');
$page = $alex->get('/');
check('C5', 'CSRF', 'Old GET link /?logout=1', 'Ignored, still signed in', str_contains($page['body'], 'Signed in as'),
    str_contains($page['body'], 'Signed in as') ? 'still signed in' : 'signed out');

$r = $alex->postForm(['action' => 'logout']);
$page = $alex->get('/');
check('C6', 'CSRF', 'Logout POST without token', '403, still signed in', $r['status'] === 403 && str_contains($page['body'], 'Signed in as'),
    "HTTP {$r['status']}");

// =========================== API ===========================================
$anon = new Client($base);
$r = $anon->api('GET', '?user_id=1');
check('P1', 'API auth', 'GET /api.php?user_id=1 with no session', '401, no notes', $r['status'] === 401 && !str_contains($r['body'], 'Alex:'),
    "HTTP {$r['status']}; " . (str_contains($r['body'], 'Alex:') ? 'NOTES LEAKED' : 'no notes'));

$r = $anon->api('POST', '', ['Content-Type: application/json'], json_encode(['id' => 1, 'title' => 'anon', 'body' => 'anon']));
check('P2', 'API auth', 'POST update with no session', '401, note unchanged', $r['status'] === 401 && ($note(1)['title'] ?? '') !== 'anon',
    "HTTP {$r['status']}");

$apiToken = $alex->csrf();
$jsonHeaders = ['Content-Type: application/json', 'X-CSRF-Token: ' . $apiToken, 'Origin: ' . $base];

$r = $alex->api('GET', '');
$decoded = json_decode($r['body'], true);
$owners = array_unique(array_map(fn($n) => (int) $n['user_id'], $decoded['notes'] ?? []));
check('P3', 'API authz', 'Alex GET /api.php', "200, only Alex's notes", $r['status'] === 200 && $owners === [1],
    "HTTP {$r['status']}; owners=" . implode(',', $owners));

$r = $alex->api('GET', '?user_id=2');
check('P4', 'API authz', "Alex GET ?user_id=2 (Sam's ID)", '403, no Sam notes', $r['status'] === 403 && !str_contains($r['body'], 'Sam:'),
    "HTTP {$r['status']}");

$r = $alex->api('POST', '', $jsonHeaders, json_encode(['id' => 3, 'title' => 'hacked', 'body' => 'hacked']));
check('P5', 'API authz', "Alex POST update for note 3 (Sam's)", '404, note unchanged', $r['status'] === 404 && ($note(3)['title'] ?? '') === $sam3['title'],
    "HTTP {$r['status']}; note 3 " . (($note(3)['title'] ?? '') === $sam3['title'] ? 'unchanged' : 'CHANGED'));

$r = $alex->api('POST', '', $jsonHeaders, '{"id": 1, "title": "x", ');
check('P6', 'API validation', 'Malformed JSON body', '400 Malformed JSON, no PHP warning', $r['status'] === 400 && str_contains($r['body'], 'Malformed JSON') && !str_contains($r['body'], 'Warning'),
    "HTTP {$r['status']} " . trim($r['body']));

$r = $alex->api('POST', '', $jsonHeaders, '[1,2,3]');
check('P7', 'API validation', 'JSON array instead of object', '400', $r['status'] === 400, "HTTP {$r['status']}");

$r = $alex->api('POST', '', $jsonHeaders, json_encode(['id' => '1', 'title' => 123]));
check('P8', 'API validation', 'Wrong types / missing body', '422 with details', $r['status'] === 422, "HTTP {$r['status']}");

$r = $alex->api('POST', '', $jsonHeaders, json_encode(['id' => 1, 'title' => str_repeat('a', 121), 'body' => 'x']));
check('P9', 'API validation', 'Title 121 characters', '422', $r['status'] === 422, "HTTP {$r['status']}");

$r = $alex->api('POST', '', $jsonHeaders, json_encode(['id' => 1, 'title' => 'x', 'body' => 'x', 'user_id' => 2]));
check('P10', 'API validation', 'Extra field user_id:2', '422 (owner cannot be set)', $r['status'] === 422, "HTTP {$r['status']}");

$r = $alex->api('POST', '', ['Content-Type: text/plain', 'X-CSRF-Token: ' . $apiToken], json_encode(['id' => 1, 'title' => 'tp', 'body' => 'tp']));
check('P11', 'API validation', 'Content-Type: text/plain (simple-request CSRF shape)', '415, unchanged', $r['status'] === 415 && ($note(1)['title'] ?? '') !== 'tp',
    "HTTP {$r['status']}");

$r = $alex->api('POST', '', ['Content-Type: application/json'], json_encode(['id' => 1, 'title' => 'nt', 'body' => 'nt']));
check('P12', 'API CSRF', 'POST without X-CSRF-Token', '403, unchanged', $r['status'] === 403 && ($note(1)['title'] ?? '') !== 'nt', "HTTP {$r['status']}");

$r = $alex->api('GET', '', ['Origin: ' . $evil]);
check('P13', 'API CORS', 'GET from Origin: http://evil.example', '403; no Access-Control-Allow-Origin',
    $r['status'] === 403 && header_value($r, 'access-control-allow-origin') === '' && header_value($r, 'access-control-allow-credentials') === '',
    "HTTP {$r['status']}; ACAO=" . (header_value($r, 'access-control-allow-origin') ?: 'none'));

$r = $alex->api('OPTIONS', '', ['Origin: ' . $evil, 'Access-Control-Request-Method: POST', 'Access-Control-Request-Headers: content-type, x-csrf-token']);
check('P14', 'API CORS', 'CORS preflight from evil origin', 'Rejected; no ACAO / Allow-Credentials',
    $r['status'] >= 400 && header_value($r, 'access-control-allow-origin') === '', "HTTP {$r['status']}; ACAO=" . (header_value($r, 'access-control-allow-origin') ?: 'none'));

$r = $alex->api('POST', '', ['Content-Type: application/json', 'X-CSRF-Token: ' . $apiToken, 'Origin: http://127.0.0.1:3000'], json_encode(['id' => 1, 'title' => 'ss', 'body' => 'ss']));
check('P15', 'API CORS', 'Same-site, different port (Origin: http://127.0.0.1:3000)', '403, unchanged', $r['status'] === 403 && ($note(1)['title'] ?? '') !== 'ss', "HTTP {$r['status']}");

$r = $alex->api('GET', '', ['Sec-Fetch-Site: cross-site']);
check('P16', 'API CORS', 'Sec-Fetch-Site: cross-site, no Origin', '403', $r['status'] === 403, "HTTP {$r['status']}");

$r = $alex->api('POST', '', $jsonHeaders, json_encode(['id' => 1, 'title' => 'Alex via API', 'body' => 'Updated through the API.']));
check('P17', 'API CRUD', 'Alex POST valid update of own note 1', '200 {"updated":1}, DB changed',
    $r['status'] === 200 && ($note(1)['title'] ?? '') === 'Alex via API', "HTTP {$r['status']} " . trim($r['body']));

$r = $alex->api('PUT', '', $jsonHeaders, '{}');
check('P18', 'API', 'PUT /api.php', '405 JSON error', $r['status'] === 405 && json_decode($r['body']) !== null, "HTTP {$r['status']}");

// =========================== Logout ========================================
$oldSid = $alex->cookies;
$t = $alex->csrf();
$alex->postForm(['csrf_token' => $t, 'action' => 'logout']);
$page = $alex->get('/');
check('O1', 'Logout', 'POST logout with token', 'Signed out, sign-in form shown', !str_contains($page['body'], 'Signed in as') && str_contains($page['body'], 'Sign in'),
    str_contains($page['body'], 'Signed in as') ? 'still signed in' : 'signed out');

$replay = new Client($base);
$replay->cookies = $oldSid;   // replay the pre-logout session cookie
$page = $replay->get('/');
$api  = $replay->api('GET', '');
check('O2', 'Logout', 'Replay old session cookie after logout', 'Not signed in; API 401',
    !str_contains($page['body'], 'Signed in as') && $api['status'] === 401, 'page ' . (str_contains($page['body'], 'Signed in as') ? 'SIGNED IN' : 'signed out') . "; API HTTP {$api['status']}");

// =========================== Errors / config ===============================
$r1 = $anon->get('/src/database.php');
$r2 = $anon->get('/schema.sql');
$leak = str_contains($r1['body'], 'function db(') || str_contains($r1['body'], 'LAB_DB_PASSWORD')
     || str_contains($r2['body'], 'CREATE TABLE');
check('E1', 'Config', 'Request /src/database.php and /schema.sql (outside public/)', '404 for both, no file content',
    $r1['status'] === 404 && $r2['status'] === 404 && !$leak,
    "HTTP {$r1['status']} and {$r2['status']}; " . ($leak ? 'FILE CONTENT SHOWN' : 'no file content'));

if ($brokenBase !== null) {
    $broken = new Client($brokenBase);
    $r = $broken->get('/');
    $leak = preg_match('/SQLSTATE|PDO|Stack trace|Access denied|\.php|LAB_DB|password/i', $r['body']);
    check('E2', 'Errors', 'Server started with wrong DB password: GET /', '500 generic message, no internals',
        $r['status'] === 500 && !$leak, "HTTP {$r['status']}; " . ($leak ? 'INTERNAL DETAILS SHOWN' : 'generic message only'));
    $r = $broken->api('GET', '');
    $leak = preg_match('/SQLSTATE|PDO|Stack trace|Access denied|\.php/i', $r['body']);
    check('E3', 'Errors', 'Same, GET /api.php (no session)', 'Generic JSON error, no internals',
        !$leak, "HTTP {$r['status']} " . trim($r['body']));
} else {
    not_run('E2', 'Errors', 'Server with wrong DB password: GET /', '500 generic message, no internals', 'Second server URL not given');
}

// =========================== Output ========================================
$pass = count(array_filter($results, fn($r) => $r[5] === 'PASS'));
$fail = count(array_filter($results, fn($r) => $r[5] === 'FAIL'));
$skip = count(array_filter($results, fn($r) => $r[5] === 'NOT RUN'));

$md  = "# Campus Notes security test results\n\n";
$md .= 'Run: ' . date('Y-m-d H:i T') . " against {$base}" . ($brokenBase ? " and {$brokenBase}" : '') . "\n";
$md .= 'PHP ' . PHP_VERSION . ', DB ' . $pdo->query('SELECT VERSION()')->fetchColumn() . "\n\n";
$md .= "| ID | Area | Check | Expected | Actual | Result |\n|---|---|---|---|---|---|\n";
foreach ($results as $row) {
    $md .= '| ' . implode(' | ', array_map(fn($c) => str_replace(['|', "\n"], ['\\|', ' '], (string) $c), $row)) . " |\n";
}
$md .= "\n**{$pass} passed, {$fail} failed, {$skip} not run.**\n";

echo $md;
@file_put_contents(__DIR__ . '/results.md', $md);
exit($fail > 0 ? 1 : 0);
