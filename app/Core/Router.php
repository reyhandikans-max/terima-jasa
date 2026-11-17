<?php
namespace App\Core;

/**
 * Modern Clean URL Router
 */
class Router
{
    private static array $routes = [];
    private static array $namedMiddlewares = [
        'auth' => \App\Middleware\AuthMiddleware::class,
        'role' => \App\Middleware\RoleMiddleware::class,
    ];

    /**
     * Register a GET route
     */
    public static function get(string $path, $handler, array $middlewares = []): void
    {
        self::addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Register a POST route
     */
    public static function post(string $path, $handler, array $middlewares = []): void
    {
        self::addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * Add route definition
     */
    private static function addRoute(string $method, string $path, $handler, array $middlewares = []): void
    {
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        self::$routes[] = [
            'method'      => $method,
            'path'        => $normalizedPath,
            'handler'     => $handler,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Dispatch the current request
     */
    public static function dispatch(Request $request): void
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = self::buildPattern($route['path']);
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches); // remove full match

                // Execute middlewares
                foreach ($route['middlewares'] as $mw) {
                    self::executeMiddleware($mw);
                }

                // Execute handler
                self::executeHandler($route['handler'], $matches, $request);
                return;
            }
        }

        // Route not found -> 404
        self::render404($path);
    }

    /**
     * Build regex from route path pattern
     */
    private static function buildPattern(string $path): string
    {
        $pattern = preg_replace('/(\{[a-zA-Z0-9_]+\}|:[a-zA-Z0-9_]+)/', '([^/]+)', $path);
        return '#^' . $pattern . '$#i';
    }

    /**
     * Execute middleware
     */
    private static function executeMiddleware(string $middleware): void
    {
        $param = null;
        if (str_contains($middleware, ':')) {
            [$name, $param] = explode(':', $middleware, 2);
        } else {
            $name = $middleware;
        }

        $class = self::$namedMiddlewares[$name] ?? $name;
        if (class_exists($class)) {
            $instance = new $class();
            if (method_exists($instance, 'handle')) {
                $instance->handle($param);
            }
        }
    }

    /**
     * Execute controller handler
     */
    private static function executeHandler($handler, array $params, Request $request): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, array_merge([$request], $params));
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $method] = $handler;
            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();
                if (method_exists($controller, $method)) {
                    call_user_func_array([$controller, $method], array_merge([$request], $params));
                    return;
                }
            }
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$controllerName, $method] = explode('@', $handler, 2);
            $fullClass = "\\App\\Controllers\\{$controllerName}";
            if (class_exists($fullClass)) {
                $controller = new $fullClass();
                if (method_exists($controller, $method)) {
                    call_user_func_array([$controller, $method], array_merge([$request], $params));
                    return;
                }
            }
        }

        Response::setStatusCode(500);
        echo "<h1>Handler Error</h1><p>Cannot execute route handler.</p>";
    }

    /**
     * Render clean 404 error page
     */
    private static function render404(string $path): void
    {
        Response::setStatusCode(404);
        view('home/404', ['requestedPath' => $path], 'main');
    }
}
