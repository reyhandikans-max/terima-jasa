<?php
namespace App\Core;

/**
 * HTTP Response Abstraction
 */
class Response
{
    public static function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

    public static function json(array $data, int $statusCode = 200): void
    {
        self::setStatusCode($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}
