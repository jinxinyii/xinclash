<?php

declare(strict_types=1);

try {
    $root = dirname(__DIR__);
    $router = require $root . '/bootstrap/app.php';
    $router->dispatch(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        $_SERVER['REQUEST_URI'] ?? '/'
    );
} catch (Throwable $exception) {
    error_log($exception->__toString());
    http_response_code(500);

    $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

    echo $debug
        ? htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8')
        : 'Something went wrong. Please try again later.';
}
