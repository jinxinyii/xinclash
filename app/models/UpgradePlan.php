<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use RuntimeException;

final class UpgradePlan
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(
        int $userId,
        int $accountId,
        int $itemId,
        int $targetLevel,
        string $priority,
        ?string $estimatedCompletionAt,
        ?string $notes
    ): int {
        $itemStatement = $this->pdo->prepare(
            'SELECT ai.current_level, ai.api_max_level, ai.upgrade_item_id
             FROM account_items ai
             INNER JOIN upgrade_items ui ON ui.id = ai.upgrade_item_id
             WHERE ai.user_id = :user_id
               AND ai.coc_account_id = :account_id
               AND ai.upgrade_item_id = :item_id
               AND ui.is_active = 1
             LIMIT 1'
        );
        $itemStatement->execute([
            'user_id' => $userId,
            'account_id' => $accountId,
            'item_id' => $itemId,
        ]);
        $item = $itemStatement->fetch();

        if (!$item) {
            throw new RuntimeException('Select an item belonging to this player account.');
        }

        $currentLevel = (int) $item['current_level'];

        if ($targetLevel <= $currentLevel) {
            throw new RuntimeException('The target level must be higher than the current level.');
        }

        if ($item['api_max_level'] !== null && $targetLevel > (int) $item['api_max_level']) {
            throw new RuntimeException('The target level cannot exceed the maximum level currently available for this player.');
        }

        $activeStatement = $this->pdo->prepare(
            'SELECT id FROM upgrade_plans
             WHERE user_id = :user_id
               AND coc_account_id = :account_id
               AND upgrade_item_id = :item_id
               AND status IN (\'planned\', \'in_progress\')
             LIMIT 1'
        );
        $activeStatement->execute([
            'user_id' => $userId,
            'account_id' => $accountId,
            'item_id' => $itemId,
        ]);

        if ($activeStatement->fetchColumn()) {
            throw new RuntimeException('This item already has an active upgrade plan.');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO upgrade_plans
                (user_id, coc_account_id, upgrade_item_id, from_level,
                 target_level, status, priority, estimated_completion_at, notes)
             VALUES
                (:user_id, :account_id, :item_id, :from_level,
                 :target_level, \'planned\', :priority, :estimated_completion_at, :notes)'
        );
        $statement->execute([
            'user_id' => $userId,
            'account_id' => $accountId,
            'item_id' => $itemId,
            'from_level' => $currentLevel,
            'target_level' => $targetLevel,
            'priority' => $priority,
            'estimated_completion_at' => $estimatedCompletionAt,
            'notes' => $notes,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function listByAccount(int $userId, int $accountId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT up.id, up.from_level, up.target_level, up.status, up.priority,
                    up.started_at, up.estimated_completion_at, up.completed_at,
                    up.notes, ui.item_name, ui.category, ai.current_level
             FROM upgrade_plans up
             INNER JOIN upgrade_items ui ON ui.id = up.upgrade_item_id
             LEFT JOIN account_items ai
               ON ai.upgrade_item_id = up.upgrade_item_id
              AND ai.user_id = up.user_id
              AND ai.coc_account_id = up.coc_account_id
             WHERE up.user_id = :user_id
               AND up.coc_account_id = :account_id
             ORDER BY FIELD(up.status, \'in_progress\', \'planned\', \'completed\', \'cancelled\'),
                      FIELD(up.priority, \'high\', \'medium\', \'low\'),
                      up.created_at DESC'
        );
        $statement->execute([
            'user_id' => $userId,
            'account_id' => $accountId,
        ]);

        return $statement->fetchAll();
    }

    public function setStatus(
        int $planId,
        int $userId,
        int $accountId,
        string $status
    ): bool {
        if (!in_array($status, ['in_progress', 'cancelled'], true)) {
            return false;
        }

        $statement = $this->pdo->prepare(
            'UPDATE upgrade_plans
             SET status = :status,
                 started_at = CASE
                    WHEN :started_status = \'in_progress\'
                    THEN COALESCE(started_at, CURRENT_TIMESTAMP)
                    ELSE started_at
                 END
             WHERE id = :plan_id
               AND user_id = :user_id
               AND coc_account_id = :account_id
               AND status IN (\'planned\', \'in_progress\')'
        );

        $statement->execute([
            'status' => $status,
            'started_status' => $status,
            'plan_id' => $planId,
            'user_id' => $userId,
            'account_id' => $accountId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function completeReached(
        int $userId,
        int $accountId,
        int $itemId,
        int $currentLevel
    ): int {
        $statement = $this->pdo->prepare(
            'UPDATE upgrade_plans
             SET status = \'completed\',
                 completed_at = CURRENT_TIMESTAMP
             WHERE user_id = :user_id
               AND coc_account_id = :account_id
               AND upgrade_item_id = :item_id
               AND status IN (\'planned\', \'in_progress\')
               AND target_level <= :current_level'
        );
        $statement->execute([
            'user_id' => $userId,
            'account_id' => $accountId,
            'item_id' => $itemId,
            'current_level' => $currentLevel,
        ]);

        return $statement->rowCount();
    }
}
