<?php
/**
 * Terima Jasa - Helper Functions
 */

if (!function_exists('env')) {
    /**
     * Get environment variable with fallback and optional .env file loader
     */
    function env(string $key, $default = null)
    {
        static $envLoaded = false;
        if (!$envLoaded) {
            $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
            $envPath = $root . '/.env';
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) continue;
                    if (str_contains($line, '=')) {
                        [$k, $v] = explode('=', $line, 2);
                        $k = trim($k);
                        $v = trim($v, " \t\n\r\0\x0B\"'");
                        if (!array_key_exists($k, $_ENV)) {
                            $_ENV[$k] = $v;
                            putenv("{$k}={$v}");
                        }
                    }
                }
            }
            $envLoaded = true;
        }

        $val = getenv($key);
        if ($val !== false) return $val;
        if (isset($_ENV[$key])) return $_ENV[$key];
        if (isset($_SERVER[$key])) return $_SERVER[$key];
        return $default;
    }
}

if (!function_exists('base_url')) {
    /**
     * Get dynamic base URL of the application
     */
    function base_url(string $path = ''): string
    {
        static $detectedBase = null;

        if ($detectedBase === null) {
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            
            // Detect subfolder script path
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $dir = str_replace('\\', '/', dirname($scriptName));
            
            // If running directly inside /public or parent
            $baseDir = rtrim($dir, '/');
            if ($baseDir === '' || $baseDir === '/') {
                $detectedBase = "{$scheme}://{$host}";
            } else {
                $detectedBase = "{$scheme}://{$host}{$baseDir}";
            }
        }

        $trimmedPath = ltrim($path, '/');
        return $trimmedPath !== '' ? "{$detectedBase}/{$trimmedPath}" : $detectedBase;
    }
}

if (!function_exists('url')) {
    /**
     * Generate an application URL
     */
    function url(string $path = ''): string
    {
        return base_url($path);
    }
}

if (!function_exists('asset')) {
    /**
     * Generate asset URL
     */
    function asset(string $path): string
    {
        $cleanPath = ltrim($path, '/');
        return base_url("assets/{$cleanPath}");
    }
}

if (!function_exists('config')) {
    /**
     * Load configuration value using dot notation (e.g. 'app.app_name')
     */
    function config(string $key, $default = null)
    {
        static $configs = [];

        $parts = explode('.', $key);
        $file = $parts[0] ?? '';

        if (!isset($configs[$file])) {
            $path = ROOT_PATH . "/config/{$file}.php";
            if (file_exists($path)) {
                $configs[$file] = require $path;
            } else {
                $configs[$file] = [];
            }
        }

        $current = $configs[$file];
        for ($i = 1; $i < count($parts); $i++) {
            $k = $parts[$i];
            if (is_array($current) && array_key_exists($k, $current)) {
                $current = $current[$k];
            } else {
                return $default;
            }
        }

        return $current ?? $default;
    }
}

if (!function_exists('view')) {
    /**
     * Helper to render view
     */
    function view(string $view, array $data = [], string $layout = 'main'): void
    {
        \App\Core\View::render($view, $data, $layout);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect helper
     */
    function redirect(string $path): void
    {
        if (!preg_match('/^https?:\/\//i', $path)) {
            $path = url($path);
        }
        if (!headers_sent()) {
            header("Location: {$path}");
        }
        if (php_sapi_name() !== 'cli') {
            exit;
        }
    }
}

if (!function_exists('session')) {
    /**
     * Access or write session variables
     */
    function session(?string $key = null, $default = null)
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if ($key === null) {
            return $_SESSION;
        }

        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML entities
     */
    function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('is_active')) {
    /**
     * Check if route matches current URI for active menu styling
     */
    function is_active(string $path): string
    {
        $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $relative = trim(str_replace($scriptDir, '', $current), '/');
        $target = trim($path, '/');
        
        if ($relative === $target || ($target === '' && ($relative === '' || $relative === 'index.php')) || ($target !== '' && str_starts_with($relative, $target))) {
            return 'active';
        }
        return '';
    }
}

if (!function_exists('auth')) {
    /**
     * Get current authenticated user
     */
    function auth(): ?array
    {
        return \App\Core\Auth::user();
    }
}

if (!function_exists('old')) {
    /**
     * Retrieve flashed old input
     */
    function old(string $key, $default = '')
    {
        return $_SESSION['old'][$key] ?? $default;
    }
}

if (!function_exists('error')) {
    /**
     * Retrieve first validation error for a field
     */
    function error(string $key): ?string
    {
        $err = $_SESSION['errors'][$key] ?? null;
        if (is_array($err)) {
            return $err[0] ?? null;
        }
        return $err;
    }
}

if (!function_exists('has_error')) {
    /**
     * Check if a field has validation error
     */
    function has_error(string $key): bool
    {
        return !empty($_SESSION['errors'][$key]);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get or generate CSRF token
     */
    function csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate HTML hidden input for CSRF token
     */
    function csrf_field(): string
    {
        $token = csrf_token();
        return '<input type="hidden" name="_csrf_token" value="' . e($token) . '">';
    }
}

if (!function_exists('verify_csrf')) {
    /**
     * Verify submitted CSRF token
     */
    function verify_csrf(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $sessionToken = $_SESSION['_csrf_token'] ?? '';
        if (empty($sessionToken) || empty($token)) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }
}
