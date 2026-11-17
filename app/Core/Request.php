<?php
namespace App\Core;

/**
 * HTTP Request Abstraction
 */
class Request
{
    private array $getParams;
    private array $postParams;
    private array $jsonParams = [];
    private array $serverParams;

    public function __construct(array $post = [], array $get = [])
    {
        $this->getParams = !empty($get) ? $get : $_GET;
        $this->postParams = !empty($post) ? $post : $_POST;
        $this->serverParams = $_SERVER;

        // Automatically parse JSON body if request is application/json or has raw JSON input
        $contentType = $this->serverParams['CONTENT_TYPE'] ?? $this->serverParams['HTTP_CONTENT_TYPE'] ?? '';
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $decoded = json_decode($rawInput, true);
            if (is_array($decoded)) {
                $this->jsonParams = $decoded;
            }
        }
    }

    public function getMethod(): string
    {
        return strtoupper($this->serverParams['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    public function ip(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        foreach ($headers as $header) {
            if (!empty($this->serverParams[$header])) {
                $ipList = explode(',', $this->serverParams[$header]);
                $ip = trim($ipList[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '127.0.0.1';
    }

    public function userAgent(): string
    {
        return substr((string)($this->serverParams['HTTP_USER_AGENT'] ?? 'Unknown'), 0, 500);
    }

    public function getPath(): string
    {
        $uri = $this->serverParams['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Strip subfolder when hosted under XAMPP subdirectories e.g. /PRD/public
        $scriptName = $this->serverParams['SCRIPT_NAME'] ?? '';
        $scriptDir  = str_replace('\\', '/', dirname($scriptName)); // e.g. /PRD/public

        // Try stripping /PRD/public first, then /PRD (parent) as fallback
        // This handles requests routed via root .htaccess (path won't have /public)
        $candidates = array_unique([$scriptDir, dirname($scriptDir)]);
        foreach ($candidates as $base) {
            $base = rtrim($base, '/');
            if ($base !== '' && $base !== '/' && str_starts_with($path, $base . '/')) {
                $path = substr($path, strlen($base));
                break;
            }
            if ($base !== '' && $base !== '/' && $path === $base) {
                $path = '/';
                break;
            }
        }

        $path = '/' . ltrim($path, '/');
        return $path === '' ? '/' : $path;
    }

    public function input(string $key, $default = null)
    {
        return $this->jsonParams[$key] ?? $this->postParams[$key] ?? $this->getParams[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->getParams, $this->postParams, $this->jsonParams);
    }
}
