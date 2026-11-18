<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AccountItem;
use App\Models\UpgradeItem;
use App\Models\UpgradePlan;
use PDO;
use RuntimeException;

final class UpgradeTrackingService
{
    private UpgradeItem $items;
    private AccountItem $accountItems;
    private UpgradePlan $plans;

    private const PET_NAMES = [
        'L.A.S.S.I',
        'Electro Owl',
        'Mighty Yak',
        'Unicorn',
        'Frosty',
        'Diggy',
        'Poison Lizard',
        'Phoenix',
        'Spirit Fox',
        'Angry Jelly',
        'Sneezy',
        'Greedy Raven',
    ];

    private const SUPER_TROOP_NAMES = [
        'Super Barbarian',
        'Super Archer',
        'Super Giant',
        'Sneaky Goblin',
        'Super Wall Breaker',
        'Rocket Balloon',
        'Super Wizard',
        'Super Dragon',
        'Inferno Dragon',
        'Super Miner',
        'Super Yeti',
        'Super Minion',
        'Super Hog Rider',
        'Super Valkyrie',
        'Super Witch',
        'Ice Hound',
        'Super Bowler',
    ];

    private const MANUAL_CATALOG = [
        ['defense', 'Cannon'],
        ['defense', 'Archer Tower'],
        ['defense', 'Mortar'],
        ['defense', 'Air Defense'],
        ['defense', 'Wizard Tower'],
        ['defense', 'Super Wizard Tower'],
        ['defense', 'Hidden Tesla'],
        ['defense', 'Bomb Tower'],
        ['defense', 'X-Bow'],
        ['defense', 'Inferno Tower'],
        ['defense', 'Eagle Artillery'],
        ['defense', 'Scattershot'],
        ['defense', 'Spell Tower'],
        ['defense', 'Monolith'],
        ['defense', 'Ricochet Cannon'],
        ['defense', 'Multi-Archer Tower'],
        ['defense', 'Firespitter'],
        ['defense', 'Revenge Tower'],
        ['defense', 'Cannon (Builder Base)'],
        ['defense', 'Archer Tower (Builder Base)'],
        ['defense', 'Double Cannon'],
        ['defense', 'Firecrackers'],
        ['defense', 'Crusher'],
        ['defense', 'Hidden Tesla (Builder Base)'],
        ['defense', 'Roaster'],
        ['defense', 'Giant Cannon'],
        ['defense', 'Mega Tesla'],
        ['defense', 'Lava Launcher'],
        ['defense', 'Multi Mortar'],
        ['defense', 'Guard Post'],
        ['defense', 'Giga Inferno'],
        ['defense', 'Giga Tesla'],
        ['trap', 'Bomb'],
        ['trap', 'Spring Trap'],
        ['trap', 'Giant Bomb'],
        ['trap', 'Air Bomb'],
        ['trap', 'Seeking Air Mine'],
        ['trap', 'Skeleton Trap'],
        ['trap', 'Tornado Trap'],
        ['trap', 'Push Trap'],
        ['trap', 'Mine (Builder Base)'],
        ['trap', 'Mega Mine'],
        ['trap', 'Push Trap (Builder Base)'],
        ['trap', 'Spring Trap (Builder Base)'],
        ['building', 'Town Hall'],
        ['building', 'Clan Castle'],
        ['building', 'Army Camp'],
        ['building', 'Barracks'],
        ['building', 'Dark Barracks'],
        ['building', 'Laboratory'],
        ['building', 'Spell Factory'],
        ['building', 'Dark Spell Factory'],
        ['building', 'Workshop'],
        ['building', 'Pet House'],
        ['building', 'Blacksmith'],
        ['building', 'Gold Mine'],
        ['building', 'Elixir Collector'],
        ['building', 'Gold Storage'],
        ['building', 'Elixir Storage'],
        ['building', 'Dark Elixir Drill'],
        ['building', 'Dark Elixir Storage'],
        ['building', 'Builder Hut'],
        ['building', 'Walls'],
        ['building', 'Builder Hall'],
        ['building', 'Clock Tower'],
        ['building', 'Star Laboratory'],
        ['building', 'Reinforcement Camp'],
        ['building', 'Gem Mine'],
    ];

    public function __construct(private readonly PDO $pdo)
    {
        $this->items = new UpgradeItem($pdo);
        $this->accountItems = new AccountItem($pdo);
        $this->plans = new UpgradePlan($pdo);
    }

    public function ensureManualCatalog(): void
    {
        $statement = $this->pdo->prepare(
            'INSERT IGNORE INTO upgrade_items
                (item_key, item_name, category, tracking_source, is_active)
             VALUES
                (:item_key, :item_name, :category, \'manual\', 1)'
        );

        foreach (self::MANUAL_CATALOG as [$category, $name]) {
            $statement->execute([
                'item_key' => 'manual:' . $category . ':' . $this->slug($name),
                'item_name' => $name,
                'category' => $category,
            ]);
        }
    }

    public function synchronizeApiItems(
        int $userId,
        int $accountId,
        array $profile
    ): array {
        $collections = [
            'troops' => $profile['troops'] ?? [],
            'heroes' => $profile['heroes'] ?? [],
            'spells' => $profile['spells'] ?? [],
            'equipment' => $profile['heroEquipment'] ?? [],
        ];

        if ($collections['equipment'] === []) {
            foreach (($profile['heroes'] ?? []) as $hero) {
                foreach (($hero['equipment'] ?? []) as $equipment) {
                    $collections['equipment'][] = $equipment;
                }
            }
        }

        $summary = [
            'items_processed' => 0,
            'levels_changed' => 0,
            'plans_completed' => 0,
            'categories' => [
                'troop' => 0,
                'super_troop' => 0,
                'spell' => 0,
                'hero' => 0,
                'pet' => 0,
                'equipment' => 0,
            ],
        ];

        $seen = [];

        foreach ($collections as $collection => $entries) {
            if (!is_array($entries)) {
                continue;
            }

            foreach ($entries as $entry) {
                if (!is_array($entry) || empty($entry['name'])) {
                    continue;
                }

                $name = trim((string) $entry['name']);
                $category = $this->resolveCategory($collection, $entry, $name);
                $village = strtolower((string) ($entry['village'] ?? 'home')) === 'builderbase'
                    ? 'builderbase'
                    : 'home';
                $itemKey = 'api:' . $category . ':' . $village . ':' . $this->slug($name);

                if (isset($seen[$itemKey])) {
                    continue;
                }

                $seen[$itemKey] = true;

                $level = filter_var($entry['level'] ?? null, FILTER_VALIDATE_INT);
                if ($level === false || $level < 0 || $level > 255) {
                    continue;
                }

                $maxLevel = filter_var($entry['maxLevel'] ?? null, FILTER_VALIDATE_INT);
                if ($maxLevel === false || $maxLevel < 1 || $maxLevel > 255) {
                    $maxLevel = null;
                }

                $itemId = $this->items->upsertApiItem($itemKey, $name, $category);
                $existing = $this->accountItems->findForAccount($userId, $accountId, $itemId);
                $previousLevel = $existing !== null ? (int) $existing['current_level'] : null;

                $this->accountItems->upsertApiLevel(
                    $userId,
                    $accountId,
                    $itemId,
                    $level,
                    $maxLevel
                );

                if ($previousLevel !== null && $level > $previousLevel) {
                    $this->recordHistory(
                        $userId,
                        $accountId,
                        $itemId,
                        $previousLevel,
                        $level,
                        'api'
                    );
                    $summary['levels_changed']++;
                }

                $summary['plans_completed'] += $this->plans->completeReached(
                    $userId,
                    $accountId,
                    $itemId,
                    $level
                );

                $summary['items_processed']++;
                $summary['categories'][$category]++;
            }
        }

        return $summary;
    }

    public function listCategory(
        int $userId,
        int $accountId,
        array $categories,
        bool $manualCatalog = false
    ): array {
        if ($manualCatalog) {
            $this->ensureManualCatalog();
        }

        return $this->accountItems->listByCategories(
            $userId,
            $accountId,
            $categories,
            $manualCatalog
        );
    }

    public function listAllItems(int $userId, int $accountId): array
    {
        return $this->accountItems->listAllForAccount($userId, $accountId);
    }

    public function listPlans(int $userId, int $accountId): array
    {
        return $this->plans->listByAccount($userId, $accountId);
    }

    public function setManualLevel(
        int $userId,
        int $accountId,
        int $itemId,
        int $level,
        array $allowedCategories
    ): void {
        if ($level < 0 || $level > 255) {
            throw new RuntimeException('Enter a valid level between 0 and 255.');
        }

        $item = $this->items->findManualItem($itemId);

        if ($item === null || !in_array($item['category'], $allowedCategories, true)) {
            throw new RuntimeException('This item is not available in the selected manual tracking category.');
        }

        $existing = $this->accountItems->findForAccount($userId, $accountId, $itemId);
        $previousLevel = $existing !== null ? (int) $existing['current_level'] : null;

        $this->accountItems->upsertManualLevel(
            $userId,
            $accountId,
            $itemId,
            $level
        );

        if ($previousLevel !== null && $level > $previousLevel) {
            $this->recordHistory(
                $userId,
                $accountId,
                $itemId,
                $previousLevel,
                $level,
                'manual'
            );
        }

        $this->plans->completeReached($userId, $accountId, $itemId, $level);
    }

    public function createPlan(
        int $userId,
        int $accountId,
        int $itemId,
        int $targetLevel,
        string $priority,
        ?string $estimatedCompletionAt,
        ?string $notes
    ): int {
        if (!in_array($priority, ['low', 'medium', 'high'], true)) {
            throw new RuntimeException('Select a valid upgrade priority.');
        }

        return $this->plans->create(
            $userId,
            $accountId,
            $itemId,
            $targetLevel,
            $priority,
            $estimatedCompletionAt,
            $notes
        );
    }

    public function updatePlanStatus(
        int $planId,
        int $userId,
        int $accountId,
        string $status
    ): bool {
        return $this->plans->setStatus($planId, $userId, $accountId, $status);
    }

    private function resolveCategory(string $collection, array $entry, string $name): string
    {
        if ($collection === 'heroes') {
            return 'hero';
        }

        if ($collection === 'spells') {
            return 'spell';
        }

        if ($collection === 'equipment') {
            return 'equipment';
        }

        if (in_array($name, self::PET_NAMES, true)) {
            return 'pet';
        }

        if (in_array($name, self::SUPER_TROOP_NAMES, true)) {
            return 'super_troop';
        }

        return 'troop';
    }

    private function recordHistory(
        int $userId,
        int $accountId,
        int $itemId,
        int $fromLevel,
        int $toLevel,
        string $source
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO upgrade_history
                (user_id, coc_account_id, upgrade_item_id, from_level,
                 to_level, completion_source, completed_at)
             VALUES
                (:user_id, :account_id, :item_id, :from_level,
                 :to_level, :source, CURRENT_TIMESTAMP)'
        );
        $statement->execute([
            'user_id' => $userId,
            'account_id' => $accountId,
            'item_id' => $itemId,
            'from_level' => $fromLevel,
            'to_level' => $toLevel,
            'source' => $source,
        ]);
    }

    private function slug(string $value): string
    {
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
}
