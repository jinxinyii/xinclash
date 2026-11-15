<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Core\Router;
use App\Helpers\Database;
use App\Helpers\Security;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

return static function (Router $router): void {
    $router->get('/', static function (): void {
        if (isset($_SESSION['user_id'])) {
            Response::redirect('/dashboard');
        }

        Response::redirect('/login');
    });

    $router->get('/health', static function (): void {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'ok', 'app' => 'xinClash']);
    });

    if (($_ENV['APP_ENV'] ?? 'local') === 'local') {
        $router->get('/health/db', static function (): void {
            header('Content-Type: application/json; charset=utf-8');

            try {
                $pdo = Database::connection();
                $database = $pdo->query('SELECT DATABASE()')->fetchColumn();
                $tableCount = $pdo->query(
                    'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
                )->fetchColumn();

                echo json_encode([
                    'status' => 'connected',
                    'database' => $database,
                    'tables' => (int) $tableCount,
                ]);
            } catch (Throwable $exception) {
                error_log($exception->__toString());
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
            }
        });
    }

    $router->get('/login', static function (): void {
        AuthMiddleware::requireGuest();
        (new AuthController(Database::connection()))->showLogin();
    });

    $router->post('/login', static function (): void {
        AuthMiddleware::requireGuest();
        (new AuthController(Database::connection()))->login();
    });

    $router->get('/register', static function (): void {
        AuthMiddleware::requireGuest();
        (new AuthController(Database::connection()))->showRegister();
    });

    $router->post('/register', static function (): void {
        AuthMiddleware::requireGuest();
        (new AuthController(Database::connection()))->register();
    });

    $router->post('/logout', static function (): void {
        AuthMiddleware::requireAuth(Database::connection());
        (new AuthController(Database::connection()))->logout();
    });

    $router->get('/dashboard', static function (): void {
        $pdo = Database::connection();
        AuthMiddleware::requireAuth($pdo);

        $name = Security::escape($_SESSION['user_name'] ?? 'Player');
        $csrfToken = Security::csrfToken();

        echo '<!doctype html>';
        echo '<html lang="en">';
        echo '<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>Dashboard | xinClash</title></head><body>';
        echo '<main>';
        echo '<h1>Welcome back, ' . $name . '!</h1>';
        echo '<p>Your xinClash account is ready.</p>';
        echo '<form method="post" action="' . Security::escape((require dirname(__DIR__) . '/config/app.php')['url']) . '/logout">';
        echo '<input type="hidden" name="_token" value="' . Security::escape($csrfToken) . '">';
        echo '<button type="submit">Log out</button>';
        echo '</form>';
        echo '</main></body></html>';
    });
};
