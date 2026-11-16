<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class GameAccount
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findAllByUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, player_tag, player_name, town_hall_level, exp_level,
                    trophies, best_trophies, is_verified, verified_at,
                    last_synced_at, sync_status, created_at
             FROM coc_accounts
             WHERE user_id = :user_id AND is_active = 1
             ORDER BY created_at DESC, id DESC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function findByIdForUser(int $accountId, int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, user_id, player_tag, player_name, town_hall_level,
                    exp_level, trophies, best_trophies, is_verified,
                    verified_at, is_active, last_synced_at, next_sync_at,
                    sync_status, created_at
             FROM coc_accounts
             WHERE id = :account_id AND user_id = :user_id
             LIMIT 1'
        );
        $statement->execute([
            'account_id' => $accountId,
            'user_id' => $userId,
        ]);

        $account = $statement->fetch();

        return $account ?: null;
    }

    public function createVerified(int $userId, string $playerTag, array $profile): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO coc_accounts
                (user_id, player_tag, player_name, town_hall_level, exp_level,
                 trophies, best_trophies, is_verified, verified_at,
                 last_synced_at, sync_status)
             VALUES
                (:user_id, :player_tag, :player_name, :town_hall_level, :exp_level,
                 :trophies, :best_trophies, 1, CURRENT_TIMESTAMP,
                 CURRENT_TIMESTAMP, \'success\')'
        );

        $statement->execute([
            'user_id' => $userId,
            'player_tag' => $playerTag,
            'player_name' => $profile['name'] ?? null,
            'town_hall_level' => $profile['townHallLevel'] ?? null,
            'exp_level' => $profile['expLevel'] ?? null,
            'trophies' => $profile['trophies'] ?? null,
            'best_trophies' => $profile['bestTrophies'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateProfile(int $accountId, int $userId, array $profile): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE coc_accounts
             SET player_name = :player_name,
                 town_hall_level = :town_hall_level,
                 exp_level = :exp_level,
                 trophies = :trophies,
                 best_trophies = :best_trophies,
                 last_synced_at = CURRENT_TIMESTAMP,
                 sync_status = \'success\'
             WHERE id = :account_id AND user_id = :user_id'
        );

        $statement->execute([
            'player_name' => $profile['name'] ?? null,
            'town_hall_level' => $profile['townHallLevel'] ?? null,
            'exp_level' => $profile['expLevel'] ?? null,
            'trophies' => $profile['trophies'] ?? null,
            'best_trophies' => $profile['bestTrophies'] ?? null,
            'account_id' => $accountId,
            'user_id' => $userId,
        ]);
    }

    public function saveSnapshot(int $accountId, int $userId, array $profile): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO player_snapshots
                (user_id, coc_account_id, snapshot_type, player_data, fetched_at)
             VALUES
                (:user_id, :account_id, \'full\', :player_data, CURRENT_TIMESTAMP)'
        );

        $statement->execute([
            'user_id' => $userId,
            'account_id' => $accountId,
            'player_data' => json_encode($profile, JSON_THROW_ON_ERROR),
        ]);
    }

    public function markSyncFailed(int $accountId, int $userId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE coc_accounts
             SET sync_status = \'failed\'
             WHERE id = :account_id AND user_id = :user_id'
        );
        $statement->execute([
            'account_id' => $accountId,
            'user_id' => $userId,
        ]);
    }
}
