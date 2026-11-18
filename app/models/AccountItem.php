<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class AccountItem
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findForAccount(int $userId, int $accountId, int $itemId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, current_level, api_max_level, target_level, data_source
             FROM account_items
             WHERE user_id = :user_id
               AND coc_account_id = :account_id
               AND upgrade_item_id = :item_id
             LIMIT 1'
        );
        $statement->execute([
            'user_id' => $userId,
            'account_id' => $accountId,
            'item_id' => $itemId,
        ]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    public function upsertApiLevel(
        int $userId,
        int $accountId,
        int $itemId,
        int $level,
        ?int $maxLevel
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO account_items
                (user_id, coc_account_id, upgrade_item_id, current_level,
                 api_max_level, data_source, last_synced_at)
             VALUES
                (:user_id, :account_id, :item_id, :current_level,
                 :api_max_level, \'api\', CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE
                current_level = :update_level,
                api_max_level = :update_max_level,
                data_source = \'api\',
                last_synced_at = CURRENT_TIMESTAMP,
                target_level = CASE
                    WHEN target_level IS NOT NULL AND target_level <= :clear_target_level
                    THEN NULL
                    ELSE target_level
                END'
        );

        $statement->execute([
            'user_id' => $userId,
            'account_id' => $accountId,
            'item_id' => $itemId,
            'current_level' => $level,
            'api_max_level' => $maxLevel,
            'update_level' => $level,
            'update_max_level' => $maxLevel,
            'clear_target_level' => $level,
        ]);
    }

    public function upsertManualLevel(
        int $userId,
        int $accountId,
        int $itemId,
        int $level
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO account_items
                (user_id, coc_account_id, upgrade_item_id, current_level,
                 api_max_level, data_source, last_synced_at)
             VALUES
                (:user_id, :account_id, :item_id, :current_level,
                 NULL, \'manual\', NULL)
             ON DUPLICATE KEY UPDATE
                current_level = :update_level,
                api_max_level = NULL,
                data_source = \'manual\',
                last_synced_at = NULL,
                target_level = CASE
                    WHEN target_level IS NOT NULL AND target_level <= :clear_target_level
                    THEN NULL
                    ELSE target_level
                END'
        );

        $statement->execute([
            'user_id' => $userId,
            'account_id' => $accountId,
            'item_id' => $itemId,
            'current_level' => $level,
            'update_level' => $level,
            'clear_target_level' => $level,
        ]);
    }

    public function listByCategories(
        int $userId,
        int $accountId,
        array $categories,
        bool $manualCatalog = false
    ): array {
        if ($categories === []) {
            return [];
        }

        $placeholders = [];
        $params = [
            'user_id' => $userId,
            'account_id' => $accountId,
        ];

        foreach (array_values($categories) as $index => $category) {
            $key = 'category_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $category;
        }

        if ($manualCatalog) {
            $sql = 'SELECT ui.id AS upgrade_item_id, ui.item_key, ui.item_name,
                           ui.category, ui.tracking_source,
                           COALESCE(ai.current_level, 0) AS current_level,
                           ai.api_max_level, ai.target_level, ai.data_source,
                           ai.last_synced_at
                    FROM upgrade_items ui
                    LEFT JOIN account_items ai
                      ON ai.upgrade_item_id = ui.id
                     AND ai.user_id = :user_id
                     AND ai.coc_account_id = :account_id
                    WHERE ui.category IN (' . implode(', ', $placeholders) . ')
                      AND ui.tracking_source = \'manual\'
                      AND ui.is_active = 1
                    ORDER BY ui.item_name ASC';
        } else {
            $sql = 'SELECT ui.id AS upgrade_item_id, ui.item_key, ui.item_name,
                           ui.category, ui.tracking_source,
                           ai.current_level, ai.api_max_level, ai.target_level,
                           ai.data_source, ai.last_synced_at
                    FROM account_items ai
                    INNER JOIN upgrade_items ui ON ui.id = ai.upgrade_item_id
                    WHERE ai.user_id = :user_id
                      AND ai.coc_account_id = :account_id
                      AND ui.category IN (' . implode(', ', $placeholders) . ')
                      AND ui.is_active = 1
                    ORDER BY ui.category ASC, ui.item_name ASC';
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function listAllForAccount(int $userId, int $accountId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ai.id, ai.upgrade_item_id, ai.current_level, ai.api_max_level,
                    ai.target_level, ui.item_name, ui.category, ui.item_key
             FROM account_items ai
             INNER JOIN upgrade_items ui ON ui.id = ai.upgrade_item_id
             WHERE ai.user_id = :user_id
               AND ai.coc_account_id = :account_id
             ORDER BY ui.category ASC, ui.item_name ASC'
        );
        $statement->execute([
            'user_id' => $userId,
            'account_id' => $accountId,
        ]);

        return $statement->fetchAll();
    }
}
