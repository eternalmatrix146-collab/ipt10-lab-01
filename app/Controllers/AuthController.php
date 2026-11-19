<?php
<<<<<<< HEAD

=======
>>>>>>> 4cebc346a79479eeaffbfda9cc62c1eab63ab568
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
<<<<<<< HEAD
use App\Services\CartService;
use Phantom\Auth\Auth;
use Phantom\Http\Controller;
use Phantom\Http\Request;
use Phantom\Http\Response;
use Phantom\Session\Session;

class AuthController extends Controller
{
    public function __construct(private Auth $auth, private Session $session, private CartService $cart)
    {
    }

    public function showLogin(): Response
    {
        return $this->view('auth.login');
    }

    public function login(Request $request): Response
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Read the guest cart token before login regenerates the session id.
        $this->cart->sessionToken();
        if (!$this->auth->attempt($data['email'], (string) $request->input('password'))) {
            $this->session->flash('errors', ['email' => ['Those credentials do not match our records.']]);
            $this->session->flash('old', ['email' => $data['email']]);
            return Response::redirect(url('/login'));
        }

        $this->cart->mergeGuestCart((int) $this->auth->id());
        $user = $this->auth->user();
        $this->flash('success', 'Welcome back, ' . $user?->displayName() . '.');

        $intended = $this->session->pull('_intended');
        if ($user instanceof User && $user->isAdmin() && !is_string($intended)) {
            return Response::redirect(route('admin.dashboard'));
        }
        return Response::redirect(is_string($intended) ? $intended : url('/'));
    }

    public function showRegister(): Response
    {
        return $this->view('auth.register');
    }

    public function register(Request $request): Response
    {
        $data = $this->validate($request, [
            'name' => 'required|string|min:2|max:100',
            'codename' => 'nullable|string|max:50',
            'email' => 'required|email|max:190|unique:users,email',
            'password' => 'required|string|min:8|max:72|confirmed',
        ]);

        $this->cart->sessionToken();
        $user = User::create([
            'name' => $data['name'],
            'codename' => ($data['codename'] ?? '') !== '' ? $data['codename'] : null,
            'email' => strtolower($data['email']),
            'password' => password_hash((string) $request->input('password'), PASSWORD_DEFAULT),
            'role' => 'customer',
        ]);

        $this->auth->login($user);
        $this->cart->mergeGuestCart((int) $user->id);
        $this->flash('success', 'Welcome to the crew, ' . $user->displayName() . '!');

        $intended = $this->session->pull('_intended');
        return Response::redirect(is_string($intended) ? $intended : url('/'));
    }

    public function logout(): Response
    {
        $this->auth->logout();
        $this->session->forget('_cart_token');
        $this->flash('info', 'You slipped out unseen. See you next time.');
        return Response::redirect(url('/'));
=======

class AuthController
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function showRegister(): void
    {
        $this->render('register', [
            'site_title' => 'Register — Nested Forum',
            'csrf_token' => csrf_token(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'flash' => [
                'error' => flash('error'),
                'success' => flash('success'),
            ],
        ]);
    }

    public function register(): void
    {
        if (!csrf_check()) {
            http_response_code(400);
            flash('error', 'Invalid or expired CSRF token.');
            redirect('/register');
            return;
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            flash('error', 'All fields are required.');
            redirect('/register');
            return;
        }

        if (strlen($username) < 3 || strlen($username) > 50) {
            flash('error', 'Username must be between 3 and 50 characters.');
            redirect('/register');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Invalid email address format.');
            redirect('/register');
            return;
        }

        if (strlen($password) < 6) {
            flash('error', 'Password must be at least 6 characters long.');
            redirect('/register');
            return;
        }

        if ($password !== $confirm) {
            flash('error', 'Passwords do not match.');
            redirect('/register');
            return;
        }

        if ($this->users->findByUsername($username)) {
            flash('error', 'Username is already taken.');
            redirect('/register');
            return;
        }

        if ($this->users->findByEmail($email)) {
            flash('error', 'Email address is already registered.');
            redirect('/register');
            return;
        }

        $userId = $this->users->create($username, $email, $password);
        if (!$userId) {
            flash('error', 'Registration failed due to a database error.');
            redirect('/register');
            return;
        }

        flash('success', 'Account created successfully! Please log in.');
        redirect('/login');
    }

    public function showLogin(): void
    {
        $this->render('login', [
            'site_title' => 'Log In — Nested Forum',
            'csrf_token' => csrf_token(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'flash' => [
                'error' => flash('error'),
                'success' => flash('success'),
            ],
        ]);
    }

    public function login(): void
    {
        if (!csrf_check()) {
            http_response_code(400);
            flash('error', 'Invalid or expired CSRF token.');
            redirect('/login');
            return;
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            flash('error', 'Username and password are required.');
            redirect('/login');
            return;
        }

        $user = $this->users->findByUsername($username);
        if (!$user || !$this->users->verifyPassword($user, $password)) {
            flash('error', 'Invalid username or password.');
            redirect('/login');
            return;
        }

        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];

        flash('success', 'Welcome back, ' . htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') . '!');
        redirect('/discussions/1');
    }

    public function logout(): void
    {
        if (!csrf_check()) {
            http_response_code(400);
            flash('error', 'Invalid CSRF token.');
            redirect('/discussions/1');
            return;
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        // Start a fresh session for the flash message
        session_start();
        session_regenerate_id(true);
        flash('success', 'You have been successfully logged out.');
        redirect('/discussions/1');
    }

    private function render(string $template, array $data): void
    {
        $mustache = new \Mustache_Engine([
            'loader' => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/../../app/Views'),
            'escape' => function ($value) {
                return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            },
        ]);
        echo $mustache->render($template, $data);
>>>>>>> 4cebc346a79479eeaffbfda9cc62c1eab63ab568
    }
}
