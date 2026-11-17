<?php
namespace App\Middleware;

use App\Core\Auth;

/**
 * Role-Based Access Control Middleware
 * Membatasi akses rute hanya untuk role pengguna yang diizinkan
 */
class RoleMiddleware implements MiddlewareInterface
{
    public function handle(?string $requiredRole = null): void
    {
        if (!Auth::check()) {
            $_SESSION['flash_error'] = 'Silakan login terlebih dahulu untuk mengakses halaman ini.';
            redirect('login');
        }

        $currentRole = Auth::role();

        if ($requiredRole && $currentRole !== $requiredRole) {
            $_SESSION['flash_error'] = "Akses Ditolak: Anda login sebagai peran [" . ucfirst($currentRole) . "] dan tidak memiliki izin mengakses area [" . ucfirst($requiredRole) . "].";

            // Redirect otomatis ke dashboard sesuai peran pengguna saat ini
            $targetUrl = Auth::dashboardUrl($currentRole);
            header("Location: {$targetUrl}");
            exit;
        }
    }
}
