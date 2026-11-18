<?php

declare(strict_types=1);

use App\Helpers\Database;
use App\Services\PlayerSyncService;
use App\Services\SupercellApiService;
use Dotenv\Dotenv;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';
Dotenv::createImmutable($root)->safeLoad();
date_default_timezone_set('Asia/Manila');

$lockPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'xinclash-sync.lock';
$lockHandle = fopen($lockPath, 'c');

if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    fwrite(STDOUT, "A xinClash synchronization process is already running.\n");
    exit(0);
}

try {
    $pdo = Database::connection();
    $batchLimit = filter_var($_ENV['SYNC_BATCH_LIMIT'] ?? 25, FILTER_VALIDATE_INT);
    $batchLimit = $batchLimit && $batchLimit > 0 ? min($batchLimit, 100) : 25;

    $statement = $pdo->prepare(
        'SELECT id, user_id
         FROM coc_accounts
         WHERE is_active = 1
           AND is_verified = 1
           AND (next_sync_at IS NULL OR next_sync_at <= CURRENT_TIMESTAMP)
         ORDER BY next_sync_at IS NOT NULL, next_sync_at ASC, id ASC
         LIMIT :batch_limit'
    );
    $statement->bindValue('batch_limit', $batchLimit, PDO::PARAM_INT);
    $statement->execute();

    $accounts = $statement->fetchAll();
    $sync = new PlayerSyncService($pdo, new SupercellApiService());
    $successCount = 0;
    $failureCount = 0;

    foreach ($accounts as $index => $account) {
        try {
            $result = $sync->sync(
                (int) $account['id'],
                (int) $account['user_id'],
                'automatic'
            );

            $summary = $result['summary'];
            $successCount++;

            fwrite(
                STDOUT,
                sprintf(
                    "Synced account %d: %d items processed, %d level changes.\n",
                    (int) $account['id'],
                    (int) $summary['items_processed'],
                    (int) $summary['levels_changed']
                )
            );
        } catch (Throwable $exception) {
            $failureCount++;
            error_log(
                'xinClash automatic sync failed for account ' .
                (int) $account['id'] . ': ' . $exception->getMessage()
            );
            fwrite(STDERR, "Failed to sync account " . (int) $account['id'] . ".\n");
        }

        if ($index < count($accounts) - 1) {
            sleep(1);
        }
    }

    fwrite(
        STDOUT,
        sprintf(
            "Automatic sync finished. Success: %d, failed: %d, due accounts: %d.\n",
            $successCount,
            $failureCount,
            count($accounts)
        )
    );
} catch (Throwable $exception) {
    error_log('xinClash cron error: ' . $exception->__toString());
    fwrite(STDERR, "The xinClash sync process could not finish.\n");
    exit(1);
} finally {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
}
