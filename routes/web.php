<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\GameAccountController;
use App\Controllers\UpgradeController;
use App\Core\Router;
use App\Helpers\Database;
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
        (new DashboardController($pdo))->index();
    });

    $router->get('/accounts', static function (): void {
        $pdo = Database::connection();
        AuthMiddleware::requireAuth($pdo);
        (new GameAccountController($pdo))->index();
    });

    $router->get('/accounts/add', static function (): void {
        $pdo = Database::connection();
        AuthMiddleware::requireAuth($pdo);
        (new GameAccountController($pdo))->showAdd();
    });

    $router->post('/accounts/store', static function (): void {
        $pdo = Database::connection();
        AuthMiddleware::requireAuth($pdo);
        (new GameAccountController($pdo))->store();
    });

    $router->get('/accounts/show', static function (): void {
        $pdo = Database::connection();
        AuthMiddleware::requireAuth($pdo);
        (new GameAccountController($pdo))->show();
    });

    $router->post('/accounts/sync', static function (): void {
        $pdo = Database::connection();
        AuthMiddleware::requireAuth($pdo);
        (new GameAccountController($pdo))->sync();
    });

    $router->get('/upgrades', static function (): void {
        $pdo = Database::connection();
        AuthMiddleware::requireAuth($pdo);
        (new UpgradeController($pdo))->index();
    });

    $upgradeCategories = [
        'defenses',
        'traps',
        'buildings',
        'troops',
        'spells',
        'heroes',
        'pets',
        'equipment',
    ];

    foreach ($upgradeCategories as $category) {
        $router->get('/upgrades/' . $category, static function () use ($category): void {
            $pdo = Database::connection();
            AuthMiddleware::requireAuth($pdo);
            (new UpgradeController($pdo))->category($category);
        });
    }

    $router->get('/upgrades/planner', static function (): void {
        $pdo = Database::connection();
        AuthMiddleware::requireAuth($pdo);
        (new UpgradeController($pdo))->planner();
    });

    $router->post('/upgrades/manual-level', static function (): void {
        $pdo = Database::connection();
        AuthMiddleware::requireAuth($pdo);
        (new UpgradeController($pdo))->saveManualLevel();
    });

    $router->post('/upgrades/planner/create', static function (): void {
        $pdo = Database::connection();
        AuthMiddleware::requireAuth($pdo);
        (new UpgradeController($pdo))->createPlan();
    });

    $router->post('/upgrades/planner/status', static function (): void {
        $pdo = Database::connection();
        AuthMiddleware::requireAuth($pdo);
        (new UpgradeController($pdo))->updatePlan();
    });
};
