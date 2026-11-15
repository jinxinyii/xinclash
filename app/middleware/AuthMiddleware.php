<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Response;
use App\Models\User;
use PDO;

final class AuthMiddleware
{
    public static function requireGuest(): void
    {
        if (isset($_SESSION['user_id'])) {
            Response::redirect('/dashboard');
        }
    }

    public static function requireAuth(PDO $pdo): void
    {
        $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$userId) {
            Response::redirect('/login');
        }

        $user = (new User($pdo))->findActiveById((int) $userId);

        if ($user === null) {
            $_SESSION = [];
            session_regenerate_id(true);
            Response::redirect('/login');
        }

        $_SESSION['user_name'] = trim(
            $user['first_name'] . ' ' . ($user['last_name'] ?? '')
        );
        $_SESSION['user_role'] = $user['role'];
    }
}
