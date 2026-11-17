<?php
namespace App\Core;

use App\Models\User;

/**
 * Authentication & Session Manager
 */
class Auth
{
    private const COOKIE_NAME = 'tj_remember';
    private const COOKIE_DAYS = 30;

    /**
     * Get currently authenticated user data
     */
    public static function user(): ?array
    {
        self::checkRememberMe();
        return session('user');
    }

    /**
     * Check if user is authenticated
     */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Get authenticated user ID
     */
    public static function id(): ?int
    {
        $u = self::user();
        return $u['id'] ?? null;
    }

    /**
     * Get authenticated user role
     */
    public static function role(): ?string
    {
        $u = self::user();
        return $u['role'] ?? null;
    }

    /**
     * Attempt login with email and password
     */
    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = User::firstWhere('email', strtolower(trim($email)));
        if (!$user) {
            // Fallback ke mock users untuk demo & kehandalan sistem saat database offline
            $mock = User::findByEmail($email);
            if ($mock && ($password === 'password123' || (isset($mock['password']) && password_verify($password, $mock['password'])))) {
                $mockModel = new User($mock);
                $mockModel->id = $mock['id'];
                self::login($mockModel, $remember);
                return true;
            }
            return false;
        }

        // Verify hashed password
        if (!password_verify($password, $user->password)) {
            return false;
        }

        // Check if account status is active
        if ($user->status !== User::STATUS_ACTIVE) {
            return false;
        }

        self::login($user, $remember);
        return true;
    }

    /**
     * Log in a user instance
     */
    public static function login(User $user, bool $remember = false): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Keamanan Session: Regenerasi ID sesi untuk mencegah Session Fixation
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        $userData = [
            'id'     => (int)$user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'role'   => $user->role,
            'status' => $user->status,
            'phone'  => $user->phone ?? '',
            'avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150',
            'badge'  => ucfirst($user->role)
        ];

        // Specific badge customizations
        if ($user->role === User::ROLE_ADMIN) {
            $userData['badge'] = 'System Administrator';
        } elseif ($user->role === User::ROLE_TEKNISI) {
            $userData['badge'] = 'Mitra Teknisi Terverifikasi';
        } else {
            $userData['badge'] = 'Customer';
        }

        $_SESSION['user'] = $userData;

        // Handle Remember Login
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $user->remember_token = $token;
            $user->save();

            $cookieValue = base64_encode($user->id . ':' . $token);
            setcookie(self::COOKIE_NAME, $cookieValue, [
                'expires'  => time() + (86400 * self::COOKIE_DAYS),
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    }

    /**
     * Check and restore session from Remember Me cookie
     */
    public static function checkRememberMe(): void
    {
        if (isset($_SESSION['user'])) {
            return;
        }

        if (empty($_COOKIE[self::COOKIE_NAME])) {
            return;
        }

        $decoded = base64_decode($_COOKIE[self::COOKIE_NAME], true);
        if (!$decoded || !str_contains($decoded, ':')) {
            return;
        }

        [$userId, $token] = explode(':', $decoded, 2);
        $user = User::find((int)$userId);

        if ($user && !empty($user->remember_token) && hash_equals($user->remember_token, $token)) {
            self::login($user, false);
        }
    }

    /**
     * Log out current user
     */
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $currentUserId = self::id();
        if ($currentUserId) {
            $user = User::find($currentUserId);
            if ($user) {
                $user->remember_token = null;
                $user->save();
            }
        }

        unset($_SESSION['user']);
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        // Delete remember cookie
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            setcookie(self::COOKIE_NAME, '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            unset($_COOKIE[self::COOKIE_NAME]);
        }
    }

    /**
     * Get redirect dashboard URL based on role
     */
    public static function dashboardUrl(?string $role = null): string
    {
        $role = $role ?? self::role();

        return match ($role) {
            User::ROLE_ADMIN    => url('admin/dashboard'),
            User::ROLE_TEKNISI  => url('technician/dashboard'),
            User::ROLE_CUSTOMER => url('customer/dashboard'),
            default             => url('login')
        };
    }
}
