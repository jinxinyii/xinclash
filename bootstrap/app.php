<?php

declare(strict_types=1);

use App\Core\Router;
use Dotenv\Dotenv;

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';

Dotenv::createImmutable($root)->safeLoad();

$appConfig = require $root . '/config/app.php';

if ($appConfig['environment'] === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', $appConfig['debug'] ? '1' : '0');
}

date_default_timezone_set('Asia/Manila');

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionConfig = require $root . '/config/session.php';

    session_name($sessionConfig['name']);
    session_set_cookie_params([
        'lifetime' => $sessionConfig['lifetime'],
        'path' => '/',
        'secure' => $sessionConfig['secure'],
        'httponly' => $sessionConfig['httponly'],
        'samesite' => $sessionConfig['samesite'],
    ]);

    session_start();
}

$router = new Router();

$registerRoutes = require $root . '/routes/web.php';
$registerRoutes($router);

return $router;
