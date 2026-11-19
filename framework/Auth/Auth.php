<?php

declare(strict_types=1);

namespace Phantom\Auth;

use Phantom\Database\Model;
use Phantom\Session\Session;

/**
 * Session-based authentication against any Model with "email" and
 * "password" (bcrypt/argon hash) columns.
 */
class Auth
{
    /** bcrypt hash of a throwaway password, verified for unknown emails so both paths cost one password_verify(). */
    private const DUMMY_HASH = '$2y$10$9giMLbqQTNFUvQC4rMnGgu7Z37kuOg/Tl45wPXB09ou1bl1ysTXy.';

    private ?Model $user = null;

    private bool $resolved = false;

    /** @param class-string<Model> $userModel */
    public function __construct(private Session $session, private string $userModel)
    {
    }

    public function attempt(string $email, string $password): bool
    {
        $model = $this->userModel;
        $user = $model::findBy('email', strtolower(trim($email)));

        // Always run password_verify so response time does not reveal whether the email exists.
        $hash = $user?->password ?? self::DUMMY_HASH;
        if (!password_verify($password, (string) $hash) || $user === null) {
            return false;
        }

        if (password_needs_rehash((string) $user->password, PASSWORD_DEFAULT)) {
            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $user->save();
        }

        $this->login($user);
        return true;
    }

    public function login(Model $user): void
    {
        $this->session->regenerate();
        $this->session->put('_auth_id', $user->getKey());
        $this->user = $user;
        $this->resolved = true;
    }

    public function logout(): void
    {
        $this->session->forget('_auth_id');
        $this->session->regenerate();
        $this->user = null;
        $this->resolved = true;
    }

    public function user(): ?Model
    {
        if (!$this->resolved) {
            $this->resolved = true;
            $id = $this->session->get('_auth_id');
            if ($id !== null) {
                $model = $this->userModel;
                $this->user = $model::find($id);
            }
        }
        return $this->user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function id(): ?int
    {
        $user = $this->user();
        return $user === null ? null : (int) $user->getKey();
    }
}
