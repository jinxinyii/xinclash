<?php

declare(strict_types=1);

namespace App\Models;

use JsonException;
use PDO;

final class PlayerSnapshot
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function createFull(int $userId, int $accountId, array $profile): void
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
}
