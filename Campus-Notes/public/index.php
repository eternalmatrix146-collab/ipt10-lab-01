<?php
/**
 * Campus Notes web app — controller only.
 * Data access, sessions, CSRF and escaping live in ../src, HTML in ../templates.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

send_security_headers();

// Only "/" is a page. PHP's built-in server (8.5+) routes unknown paths such as
// /src/database.php to this file, so answer those with a plain 404.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path !== '/' && $path !== '/index.php') {
    render('error', ['message' => 'Page not found.'], 404);
}

start_secure_session();

$db     = db();
$userId = current_user_id();

// ---------------------------------------------------------------------------
// State-changing actions: POST only, same origin, valid CSRF token (issue 5)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!request_origin_is_trusted() || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
        render('error', ['message' => 'Request rejected: the form expired or came from another site. Reload the page and try again.'], 403);
    }

    $action = $_POST['action'] ?? '';

    // Actions for signed-out visitors
    if ($userId === null) {
        if ($action === 'register') {
            $email    = $_POST['email'] ?? null;
            $password = $_POST['password'] ?? null;
            $errors   = validate_registration($email, $password);
            if ($errors) {
                array_map('flash', $errors);
                redirect('/');
            }
            // Role is NOT read from the request (issue 7).
            if (register_user($db, $email, $password)) {
                flash('Account created. You can sign in now.');
            } else {
                flash('Could not create that account.');
            }
            redirect('/');
        }

        if ($action === 'login') {
            $user = authenticate($db, $_POST['email'] ?? null, $_POST['password'] ?? null);
            if ($user !== null) {
                login_user($user);           // regenerates the session ID (issue 6)
                redirect('/');
            }
            flash('Invalid e-mail or password.');   // generic, does not echo input (issue 4)
            redirect('/');
        }

        render('error', ['message' => 'Please sign in first.'], 401);
    }

    // Actions for signed-in users. Owner is always the session user (issues 3, 7).
    switch ($action) {
        case 'logout':
            logout_user();
            redirect('/');

        case 'save':
            $title = $_POST['title'] ?? null;
            $body  = $_POST['body'] ?? null;
            $errors = validate_note_input($title, $body);
            if ($errors) {
                array_map('flash', $errors);
                redirect('/');
            }
            create_note($db, $userId, $title, $body);
            flash('Note saved.');
            redirect('/');

        case 'update':
            $noteId = parse_id($_POST['id'] ?? null);
            $title  = $_POST['title'] ?? null;
            $body   = $_POST['body'] ?? null;
            $errors = validate_note_input($title, $body);
            if ($noteId === null) {
                $errors[] = 'Invalid note.';
            }
            if ($errors) {
                array_map('flash', $errors);
                redirect('/');
            }
            if (!update_note_for_user($db, $noteId, $userId, $title, $body)) {
                render('error', ['message' => 'Note not found.'], 404);
            }
            flash('Note updated.');
            redirect('/');

        case 'delete':
            $noteId = parse_id($_POST['id'] ?? null);
            if ($noteId === null || !delete_note_for_user($db, $noteId, $userId)) {
                render('error', ['message' => 'Note not found.'], 404);
            }
            flash('Note deleted.');
            redirect('/');

        default:
            render('error', ['message' => 'Unknown action.'], 400);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
    header('Allow: GET, HEAD, POST');
    render('error', ['message' => 'Method not allowed.'], 405);
}

// ---------------------------------------------------------------------------
// Read-only page. The old ?delete= and ?logout= GET links no longer exist.
// ---------------------------------------------------------------------------
$status  = 200;
$notes   = [];
$editing = null;

if ($userId !== null) {
    $notes = notes_for_user($db, $userId);
    if (isset($_GET['edit'])) {
        $editId  = parse_id($_GET['edit']);
        $editing = $editId === null ? null : find_note_for_user($db, $editId, $userId);
        if ($editing === null) {
            flash('Note not found.');
            $status = 404;
        }
    }
}

render('page', [
    'messages'  => take_flashes(),
    'userId'    => $userId,
    'email'     => $_SESSION['email'] ?? '',
    'role'      => $_SESSION['role'] ?? '',
    'notes'     => $notes,
    'editing'   => $editing,
    'csrfToken' => csrf_token(),
], $status);
