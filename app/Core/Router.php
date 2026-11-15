<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$this->normalizePath($path)] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$this->normalizePath($path)] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $basePath = rtrim(parse_url($config['url'], PHP_URL_PATH) ?: '', '/');

        if (
            $basePath !== ''
            && strncasecmp($path, $basePath, strlen($basePath)) === 0
            && (
                strlen($path) === strlen($basePath)
                || $path[strlen($basePath)] === '/'
            )
        ) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }

        $path = $this->normalizePath($path);
        $handler = $this->routes[strtoupper($method)][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo '404 - Page not found';
            return;
        }

        $handler();
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/' . trim($path, '/');

        return $normalized === '//' ? '/' : $normalized;
    }
}
