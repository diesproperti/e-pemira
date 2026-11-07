<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<string, array{handler: array{0: class-string, 1: string}, middlewares: list<string>}>> */
    private array $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $middlewares
     */
    public function get(string $path, array $handler, array $middlewares = []): void
    {
        $this->routes['GET'][$this->normalizePath($path)] = [
            'handler'     => $handler,
            'middlewares' => $middlewares,
        ];
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $middlewares
     */
    public function post(string $path, array $handler, array $middlewares = []): void
    {
        if (!in_array('csrf', $middlewares, true)) {
            $middlewares[] = 'csrf';
        }
        $this->routes['POST'][$this->normalizePath($path)] = [
            'handler'     => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = $this->resolveCurrentPath();

        $route = $this->routes[$method][$path] ?? null;

        if ($route === null) {
            http_response_code(404);
            $view404 = base_path('app/Views/layouts/404.php');
            if (is_file($view404)) {
                require $view404;
            } else {
                echo '404 Halaman Tidak Ditemukan';
            }
            return;
        }

        foreach ($route['middlewares'] as $mw) {
            Middleware::handle($mw);
        }

        [$class, $action] = $route['handler'];
        $controller = new $class();
        $controller->{$action}();
    }

    private function resolveCurrentPath(): string
    {
        if (!empty($_GET['route']) && is_string($_GET['route'])) {
            return $this->normalizePath($_GET['route']);
        }

        $requestUri = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $baseDir = rtrim(dirname($scriptName), '/');

        if ($baseDir !== '' && $baseDir !== '/' && str_starts_with($requestUri, $baseDir)) {
            $requestUri = substr($requestUri, strlen($baseDir));
        }

        // Also strip /index.php if present in URL
        if (str_starts_with($requestUri, '/index.php')) {
            $requestUri = substr($requestUri, strlen('/index.php'));
        }

        return $this->normalizePath($requestUri);
    }

    private function normalizePath(string $path): string
    {
        $trimmed = '/' . trim($path, '/');
        return $trimmed === '//' ? '/' : $trimmed;
    }
}

