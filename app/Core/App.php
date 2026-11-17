<?php
namespace App\Core;

/**
 * Application Bootstrap & Kernel
 */
class App
{
    public static function run(): void
    {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Restore authenticated session from Remember Me cookie if available
        Auth::checkRememberMe();

        // Set timezone
        date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));

        // Load Routes
        $routesPath = ROOT_PATH . '/routes/web.php';
        if (file_exists($routesPath)) {
            require_once $routesPath;
        }

        $apiRoutesPath = ROOT_PATH . '/routes/api.php';
        if (file_exists($apiRoutesPath)) {
            require_once $apiRoutesPath;
        }

        // Handle Request Dispatch
        $request = new Request();
        Router::dispatch($request);
    }
}
