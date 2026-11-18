<?php

declare(strict_types=1);

use App\Helpers\Database;
use App\Services\UpgradeTrackingService;
use Dotenv\Dotenv;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';
Dotenv::createImmutable($root)->safeLoad();

$tracking = new UpgradeTrackingService(Database::connection());
$tracking->ensureManualCatalog();

fwrite(STDOUT, "Manual defenses, traps, and building catalog initialized.\n");
