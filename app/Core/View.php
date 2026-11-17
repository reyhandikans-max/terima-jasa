<?php
namespace App\Core;

/**
 * Modern Template View Engine
 */
class View
{
    /**
     * Render view with layout
     */
    public static function render(string $viewPath, array $data = [], string $layout = 'main'): void
    {
        extract($data);

        // Normalize view path
        $viewFile = APP_PATH . '/Views/' . str_replace('.', '/', $viewPath) . '.php';

        if (!file_exists($viewFile)) {
            Response::setStatusCode(500);
            echo "<h1>View Error</h1><p>View file not found: <code>{$viewFile}</code></p>";
            return;
        }

        // Buffer view content
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render inside layout if requested
        if ($layout) {
            $layoutFile = APP_PATH . '/Views/layouts/' . $layout . '.php';
            if (file_exists($layoutFile)) {
                require $layoutFile;
                return;
            }
        }

        echo $content;
    }

    /**
     * Include a reusable partial view
     */
    public static function partial(string $partialName, array $data = []): void
    {
        extract($data);
        $file = APP_PATH . '/Views/partials/' . str_replace('.', '/', $partialName) . '.php';

        if (file_exists($file)) {
            require $file;
        } else {
            echo "<!-- Partial {$partialName} not found -->";
        }
    }
}
