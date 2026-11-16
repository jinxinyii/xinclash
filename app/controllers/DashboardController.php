<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\Security;
use PDO;

final class DashboardController
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function index(): void
    {
        $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$userId) {
            Response::redirect('/login');
        }

        $accountsStatement = $this->pdo->prepare(
            'SELECT id, player_tag, player_name, town_hall_level, exp_level, trophies,
                    best_trophies, is_verified, last_synced_at, sync_status
             FROM coc_accounts
             WHERE user_id = :user_id AND is_active = 1
             ORDER BY id ASC'
        );
        $accountsStatement->execute(['user_id' => $userId]);
        $accounts = $accountsStatement->fetchAll();

        $selectedAccount = null;
        $requestedAccountId = filter_var($_GET['account'] ?? null, FILTER_VALIDATE_INT);

        if ($requestedAccountId) {
            foreach ($accounts as $account) {
                if ((int) $account['id'] === (int) $requestedAccountId) {
                    $selectedAccount = $account;
                    break;
                }
            }
        }

        if ($selectedAccount === null && $accounts !== []) {
            $selectedAccount = $accounts[0];
        }

        $stats = [
            'total_items' => 0,
            'defenses' => 0,
            'troops' => 0,
            'spells' => 0,
            'heroes' => 0,
            'maxed_items' => 0,
        ];
        $planStats = [
            'planned' => 0,
            'in_progress' => 0,
            'completed' => 0,
        ];
        $recentHistory = [];
        $recentPlans = [];

        if ($selectedAccount !== null) {
            $accountId = (int) $selectedAccount['id'];

            $statsStatement = $this->pdo->prepare(
                "SELECT
                    COUNT(*) AS total_items,
                    COALESCE(SUM(ui.category = 'defense'), 0) AS defenses,
                    COALESCE(SUM(ui.category IN ('troop', 'super_troop')), 0) AS troops,
                    COALESCE(SUM(ui.category = 'spell'), 0) AS spells,
                    COALESCE(SUM(ui.category = 'hero'), 0) AS heroes,
                    COALESCE(SUM(ui.max_level IS NOT NULL AND ai.current_level >= ui.max_level), 0) AS maxed_items
                 FROM account_items ai
                 INNER JOIN upgrade_items ui ON ui.id = ai.upgrade_item_id
                 WHERE ai.user_id = :user_id AND ai.coc_account_id = :account_id"
            );
            $statsStatement->execute([
                'user_id' => $userId,
                'account_id' => $accountId,
            ]);
            $stats = array_merge($stats, $statsStatement->fetch() ?: []);

            $planStatement = $this->pdo->prepare(
                "SELECT
                    COALESCE(SUM(status = 'planned'), 0) AS planned,
                    COALESCE(SUM(status = 'in_progress'), 0) AS in_progress,
                    COALESCE(SUM(status = 'completed'), 0) AS completed
                 FROM upgrade_plans
                 WHERE user_id = :user_id AND coc_account_id = :account_id"
            );
            $planStatement->execute([
                'user_id' => $userId,
                'account_id' => $accountId,
            ]);
            $planStats = array_merge($planStats, $planStatement->fetch() ?: []);

            $historyStatement = $this->pdo->prepare(
                'SELECT uh.from_level, uh.to_level, uh.completed_at,
                        ui.item_name, ui.category
                 FROM upgrade_history uh
                 INNER JOIN upgrade_items ui ON ui.id = uh.upgrade_item_id
                 WHERE uh.user_id = :user_id AND uh.coc_account_id = :account_id
                 ORDER BY uh.completed_at DESC, uh.id DESC
                 LIMIT 5'
            );
            $historyStatement->execute([
                'user_id' => $userId,
                'account_id' => $accountId,
            ]);
            $recentHistory = $historyStatement->fetchAll();

            $recentPlansStatement = $this->pdo->prepare(
                "SELECT up.from_level, up.target_level, up.status, up.priority,
                        up.estimated_completion_at, ui.item_name, ui.category
                 FROM upgrade_plans up
                 INNER JOIN upgrade_items ui ON ui.id = up.upgrade_item_id
                 WHERE up.user_id = :user_id
                   AND up.coc_account_id = :account_id
                   AND up.status IN ('planned', 'in_progress')
                 ORDER BY FIELD(up.status, 'in_progress', 'planned'),
                          FIELD(up.priority, 'high', 'medium', 'low'),
                          up.estimated_completion_at IS NULL,
                          up.estimated_completion_at ASC
                 LIMIT 5"
            );
            $recentPlansStatement->execute([
                'user_id' => $userId,
                'account_id' => $accountId,
            ]);
            $recentPlans = $recentPlansStatement->fetchAll();
        }

        $userName = trim((string) ($_SESSION['user_name'] ?? 'Player'));
        $config = require dirname(__DIR__, 2) . '/config/app.php';

        Response::render('Dashboard/index', [
            'userName' => $userName !== '' ? $userName : 'Player',
            'userRole' => (string) ($_SESSION['user_role'] ?? 'member'),
            'baseUrl' => rtrim($config['url'], '/'),
            'csrfToken' => Security::csrfToken(),
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
            'stats' => $stats,
            'planStats' => $planStats,
            'recentHistory' => $recentHistory,
            'recentPlans' => $recentPlans,
        ]);
    }
}
