<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class UpgradeItem
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function upsertApiItem(string $itemKey, string $itemName, string $category): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO upgrade_items
                (item_key, item_name, category, tracking_source, is_active)
             VALUES
                (:item_key, :item_name, :category, \'api\', 1)
             ON DUPLICATE KEY UPDATE
                item_name = :update_name,
                category = :update_category,
                tracking_source = \'api\',
                is_active = 1'
        );

        $statement->execute([
            'item_key' => $itemKey,
            'item_name' => $itemName,
            'category' => $category,
            'update_name' => $itemName,
            'update_category' => $category,
        ]);

        $lookup = $this->pdo->prepare(
            'SELECT id FROM upgrade_items WHERE item_key = :item_key LIMIT 1'
        );
        $lookup->execute(['item_key' => $itemKey]);

        return (int) $lookup->fetchColumn();
    }

    public function findManualItem(int $itemId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, item_key, item_name, category
             FROM upgrade_items
             WHERE id = :item_id
               AND tracking_source = \'manual\'
               AND is_active = 1
             LIMIT 1'
        );
        $statement->execute(['item_id' => $itemId]);
        $item = $statement->fetch();

        return $item ?: null;
    }
}
