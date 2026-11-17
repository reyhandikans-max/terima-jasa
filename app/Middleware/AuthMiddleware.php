<?php
namespace App\Middleware;

use App\Core\Auth;

/**
 * Authentication Middleware
 * Memastikan pengguna telah login sebelum mengakses halaman
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(?string $param = null): void
    {
        if (!Auth::check()) {
            $_SESSION['flash_error'] = 'Silakan login terlebih dahulu untuk mengakses halaman ini.';
            redirect('login');
        }
    }
}
