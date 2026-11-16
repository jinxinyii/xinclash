<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GameAccount;
use PDO;
use RuntimeException;
use Throwable;

final class PlayerSyncService
{
    private GameAccount $accounts;

    public function __construct(
        private readonly PDO $pdo,
        private readonly SupercellApiService $api
    ) {
        $this->accounts = new GameAccount($pdo);
    }

    public function sync(int $accountId, int $userId): array
    {
        $account = $this->accounts->findByIdForUser($accountId, $userId);

        if ($account === null || (int) $account['is_active'] !== 1) {
            throw new RuntimeException('The selected Clash account could not be found.');
        }

        if ((int) $account['is_verified'] !== 1) {
            throw new RuntimeException('Verify ownership of this player account before synchronizing it.');
        }

        $startedAt = date('Y-m-d H:i:s');
        $statusCode = null;

        try {
            $profile = $this->api->getPlayer($account['player_tag']);
            $this->pdo->beginTransaction();

            $this->accounts->updateProfile($accountId, $userId, $profile);
            $this->accounts->saveSnapshot($accountId, $userId, $profile);

            $log = $this->pdo->prepare(
                'INSERT INTO sync_logs
                    (user_id, coc_account_id, sync_type, status, started_at,
                     finished_at, http_status, items_updated)
                 VALUES
                    (:user_id, :account_id, \'manual\', \'success\',
                     :started_at, CURRENT_TIMESTAMP, 200, 1)'
            );
            $log->execute([
                'user_id' => $userId,
                'account_id' => $accountId,
                'started_at' => $startedAt,
            ]);

            $this->pdo->commit();

            return $profile;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $statusCode = $exception instanceof SupercellApiException
                ? $exception->httpStatus()
                : null;

            try {
                $this->accounts->markSyncFailed($accountId, $userId);

                $log = $this->pdo->prepare(
                    'INSERT INTO sync_logs
                        (user_id, coc_account_id, sync_type, status, started_at,
                         finished_at, http_status, items_updated, error_code, error_message)
                     VALUES
                        (:user_id, :account_id, \'manual\', \'failed\',
                         :started_at, CURRENT_TIMESTAMP, :http_status, 0,
                         :error_code, :error_message)'
                );
                $log->execute([
                    'user_id' => $userId,
                    'account_id' => $accountId,
                    'started_at' => $startedAt,
                    'http_status' => $statusCode ?: null,
                    'error_code' => $statusCode ? 'HTTP_' . $statusCode : 'SYNC_FAILED',
                    'error_message' => substr($exception->getMessage(), 0, 1000),
                ]);
            } catch (Throwable $loggingException) {
                error_log('xinClash sync failure could not be recorded: ' . $loggingException->__toString());
            }

            throw $exception;
        }
    }
}
