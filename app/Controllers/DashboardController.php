<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;

/**
 * DashboardController - Router Pusat Dashboard Berdasarkan Role
 */
class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        if (!Auth::check()) {
            $_SESSION['flash_error'] = 'Silakan login terlebih dahulu untuk mengakses dashboard.';
            $this->redirect('login');
            return;
        }

        // Redirect otomatis ke dashboard peran pengguna
        $this->redirect(Auth::dashboardUrl());
    }
}
