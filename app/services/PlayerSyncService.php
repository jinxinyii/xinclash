<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GameAccount;
use App\Models\PlayerSnapshot;
use App\Models\SyncLog;
use PDO;
use RuntimeException;
use Throwable;

final class PlayerSyncService
{
    private GameAccount $accounts;
    private PlayerSnapshot $snapshots;
    private SyncLog $logs;
    private UpgradeTrackingService $upgradeTracking;

    public function __construct(
        private readonly PDO $pdo,
        private readonly SupercellApiService $api
    ) {
        $this->accounts = new GameAccount($pdo);
        $this->snapshots = new PlayerSnapshot($pdo);
        $this->logs = new SyncLog($pdo);
        $this->upgradeTracking = new UpgradeTrackingService($pdo);
    }

    public function sync(int $accountId, int $userId, string $syncType = 'manual'): array
    {
        if (!in_array($syncType, ['manual', 'automatic'], true)) {
            throw new RuntimeException('Invalid synchronization type.');
        }

        $account = $this->accounts->findByIdForUser($accountId, $userId);

        if ($account === null || (int) $account['is_active'] !== 1) {
            throw new RuntimeException('The selected Clash account could not be found.');
        }

        if ((int) $account['is_verified'] !== 1) {
            throw new RuntimeException('Verify ownership of this player account before synchronizing it.');
        }

        $startedAt = date('Y-m-d H:i:s');

        try {
            $profile = $this->api->getPlayer($account['player_tag']);

            $this->pdo->beginTransaction();

            $this->accounts->updateProfile($accountId, $userId, $profile);
            $this->snapshots->createFull($userId, $accountId, $profile);
            $summary = $this->upgradeTracking->synchronizeApiItems(
                $userId,
                $accountId,
                $profile
            );

            $this->logs->success(
                $userId,
                $accountId,
                $syncType,
                $startedAt,
                (int) $summary['items_processed']
            );

            $this->pdo->commit();

            return [
                'profile' => $profile,
                'summary' => $summary,
            ];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $statusCode = $exception instanceof SupercellApiException
                ? $exception->httpStatus()
                : null;

            try {
                $this->accounts->markSyncFailed($accountId, $userId);
                $this->logs->failure(
                    $userId,
                    $accountId,
                    $syncType,
                    $startedAt,
                    $statusCode ?: null,
                    $statusCode ? 'HTTP_' . $statusCode : 'SYNC_FAILED',
                    $exception->getMessage()
                );
            } catch (Throwable $loggingException) {
                error_log('xinClash sync failure could not be recorded: ' . $loggingException->__toString());
            }

            throw $exception;
        }
    }
}
