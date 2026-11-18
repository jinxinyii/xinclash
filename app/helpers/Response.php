<?php

declare(strict_types=1);

namespace App\Helpers;

final class Response
{
    public static function redirect(string $path): never
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $baseUrl = rtrim($config['url'], '/');
        $path = '/' . ltrim($path, '/');

        header('Location: ' . $baseUrl . $path);
        exit;
    }

    public static function render(string $view, array $data = []): void
    {
        $viewPath = dirname(__DIR__) . '/views/' . strtolower($view) . '.php';

        if (!is_file($viewPath)) {
            http_response_code(500);
            echo 'The requested page could not be loaded.';
            return;
        }

        extract($data, EXTR_SKIP);
        require $viewPath;
    }

    public static function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);

        return is_string($message) ? $message : null;
    }
}
