<?php
namespace App\Core;

/**
 * Base Controller
 */
abstract class Controller
{
    /**
     * Render a view file
     */
    protected function view(string $viewPath, array $data = [], string $layout = 'main'): void
    {
        View::render($viewPath, $data, $layout);
    }

    /**
     * Return JSON response
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        Response::json($data, $statusCode);
    }

    /**
     * Redirect to path
     */
    protected function redirect(string $path): void
    {
        redirect($path);
    }
}
