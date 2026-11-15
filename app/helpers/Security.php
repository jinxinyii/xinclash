<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

final class Security
{
    public static function csrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('A session is required before generating a CSRF token.');
        }

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function verifyCsrfToken(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION['_csrf_token'])
            && hash_equals($_SESSION['_csrf_token'], $token);
    }

    public static function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function ipHash(): string
    {
        $key = $_ENV['APP_KEY'] ?? '';

        if (strlen($key) < 32) {
            throw new RuntimeException('APP_KEY must contain at least 32 characters.');
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        return hash_hmac('sha256', $ip, $key);
    }
}
