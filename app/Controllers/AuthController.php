<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
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
    }
}
