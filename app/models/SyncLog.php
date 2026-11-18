<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class SyncLog
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function success(
        int $userId,
        int $accountId,
        string $syncType,
        string $startedAt,
        int $itemsUpdated
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO sync_logs
                (user_id, coc_account_id, sync_type, status, started_at,
                 finished_at, http_status, items_updated)
             VALUES
                (:user_id, :account_id, :sync_type, \'success\',
                 :started_at, CURRENT_TIMESTAMP, 200, :items_updated)'
        );
        $statement->execute([
            'user_id' => $userId,
            'account_id' => $accountId,
            'sync_type' => $syncType,
            'started_at' => $startedAt,
            'items_updated' => $itemsUpdated,
        ]);
    }

    public function failure(
        int $userId,
        int $accountId,
        string $syncType,
        string $startedAt,
        ?int $httpStatus,
        string $errorCode,
        string $errorMessage
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO sync_logs
                (user_id, coc_account_id, sync_type, status, started_at,
                 finished_at, http_status, items_updated, error_code, error_message)
             VALUES
                (:user_id, :account_id, :sync_type, \'failed\',
                 :started_at, CURRENT_TIMESTAMP, :http_status, 0,
                 :error_code, :error_message)'
        );
        $statement->execute([
            'user_id' => $userId,
            'account_id' => $accountId,
            'sync_type' => $syncType,
            'started_at' => $startedAt,
            'http_status' => $httpStatus,
            'error_code' => $errorCode,
            'error_message' => mb_substr($errorMessage, 0, 1000),
        ]);
    }
}
