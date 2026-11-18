<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\Security;
use App\Models\GameAccount;
use App\Services\UpgradeTrackingService;
use PDO;
use RuntimeException;
use Throwable;

final class UpgradeController
{
    private const CATEGORIES = [
        'defenses' => [
            'title' => 'Defenses',
            'description' => 'Manually track the levels of your defensive structures.',
            'categories' => ['defense'],
            'manual' => true,
            'view' => 'Upgrades/defenses',
        ],
        'traps' => [
            'title' => 'Traps',
            'description' => 'Keep your trap levels up to date manually.',
            'categories' => ['trap'],
            'manual' => true,
            'view' => 'Upgrades/traps',
        ],
        'buildings' => [
            'title' => 'Buildings',
            'description' => 'Track your village buildings and support structures.',
            'categories' => ['building'],
            'manual' => true,
            'view' => 'Upgrades/buildings',
        ],
        'troops' => [
            'title' => 'Troops and siege machines',
            'description' => 'API-synchronized troop levels, including Builder Base units and siege machines.',
            'categories' => ['troop', 'super_troop'],
            'manual' => false,
            'view' => 'Upgrades/troops',
        ],
        'spells' => [
            'title' => 'Spells',
            'description' => 'API-synchronized spell levels.',
            'categories' => ['spell'],
            'manual' => false,
            'view' => 'Upgrades/spells',
        ],
        'heroes' => [
            'title' => 'Heroes',
            'description' => 'API-synchronized Home Village and Builder Base heroes.',
            'categories' => ['hero'],
            'manual' => false,
            'view' => 'Upgrades/heroes',
        ],
        'pets' => [
            'title' => 'Pets',
            'description' => 'API-synchronized Hero Pet levels.',
            'categories' => ['pet'],
            'manual' => false,
            'view' => 'Upgrades/pets',
        ],
        'equipment' => [
            'title' => 'Hero equipment',
            'description' => 'API-synchronized equipment levels.',
            'categories' => ['equipment'],
            'manual' => false,
            'view' => 'Upgrades/equipment',
        ],
    ];

    private GameAccount $accounts;
    private UpgradeTrackingService $tracking;

    public function __construct(private readonly PDO $pdo)
    {
        $this->accounts = new GameAccount($pdo);
        $this->tracking = new UpgradeTrackingService($pdo);
    }

    public function index(): void
    {
        $userId = $this->userId();
        $accounts = $this->accounts->findAllByUser($userId);
        $selectedAccount = $this->selectAccount($accounts);

        if ($selectedAccount === null) {
            Response::flash('error', 'Connect a verified Clash of Clans account before using the upgrade tracker.');
            Response::redirect('/accounts');
        }

        Response::render('Upgrades/index', [
            'baseUrl' => $this->baseUrl(),
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
        ]);
    }

    public function category(string $key): void
    {
        $config = self::CATEGORIES[$key] ?? null;

        if ($config === null) {
            http_response_code(404);
            echo 'Upgrade category not found.';
            return;
        }

        $userId = $this->userId();
        $accounts = $this->accounts->findAllByUser($userId);
        $selectedAccount = $this->selectAccount($accounts);

        if ($selectedAccount === null) {
            Response::flash('error', 'Connect a verified Clash of Clans account before using the upgrade tracker.');
            Response::redirect('/accounts');
        }

        $accountId = (int) $selectedAccount['id'];
        $items = $this->tracking->listCategory(
            $userId,
            $accountId,
            $config['categories'],
            $config['manual']
        );

        Response::render($config['view'], [
            'baseUrl' => $this->baseUrl(),
            'csrfToken' => Security::csrfToken(),
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
            'items' => $items,
            'categoryKey' => $key,
            'categoryTitle' => $config['title'],
            'categoryDescription' => $config['description'],
            'isManual' => $config['manual'],
            'success' => Response::flash('success'),
            'error' => Response::flash('error'),
        ]);
    }

    public function planner(): void
    {
        $userId = $this->userId();
        $accounts = $this->accounts->findAllByUser($userId);
        $selectedAccount = $this->selectAccount($accounts);

        if ($selectedAccount === null) {
            Response::flash('error', 'Connect a verified Clash of Clans account before creating upgrade plans.');
            Response::redirect('/accounts');
        }

        $accountId = (int) $selectedAccount['id'];

        Response::render('Upgrades/planner', [
            'baseUrl' => $this->baseUrl(),
            'csrfToken' => Security::csrfToken(),
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
            'items' => $this->tracking->listAllItems($userId, $accountId),
            'plans' => $this->tracking->listPlans($userId, $accountId),
            'success' => Response::flash('success'),
            'error' => Response::flash('error'),
        ]);
    }

    public function saveManualLevel(): void
    {
        if (!$this->verifyCsrf()) {
            http_response_code(419);
            echo 'Your session expired. Refresh the page and try again.';
            return;
        }

        $userId = $this->userId();
        $accountId = filter_var($_POST['account_id'] ?? null, FILTER_VALIDATE_INT);
        $itemId = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
        $level = filter_var($_POST['current_level'] ?? null, FILTER_VALIDATE_INT);
        $category = (string) ($_POST['category'] ?? 'defenses');

        if (!$accountId || !$itemId || $level === false || !isset(self::CATEGORIES[$category]) || !self::CATEGORIES[$category]['manual']) {
            Response::flash('error', 'Please provide a valid item and level.');
            Response::redirect('/upgrades/' . (isset(self::CATEGORIES[$category]) ? $category : 'defenses'));
        }

        $account = $this->accounts->findByIdForUser((int) $accountId, $userId);

        if ($account === null || (int) $account['is_active'] !== 1) {
            http_response_code(404);
            echo 'Clash account not found.';
            return;
        }

        try {
            $this->tracking->setManualLevel(
                $userId,
                (int) $accountId,
                (int) $itemId,
                (int) $level,
                self::CATEGORIES[$category]['categories']
            );
            Response::flash('success', 'The item level was saved.');
        } catch (RuntimeException $exception) {
            Response::flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            error_log($exception->__toString());
            Response::flash('error', 'The item level could not be saved. Please try again.');
        }

        Response::redirect('/upgrades/' . $category . '?account=' . (int) $accountId);
    }

    public function createPlan(): void
    {
        if (!$this->verifyCsrf()) {
            http_response_code(419);
            echo 'Your session expired. Refresh the page and try again.';
            return;
        }

        $userId = $this->userId();
        $accountId = filter_var($_POST['account_id'] ?? null, FILTER_VALIDATE_INT);
        $itemId = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
        $targetLevel = filter_var($_POST['target_level'] ?? null, FILTER_VALIDATE_INT);
        $priority = (string) ($_POST['priority'] ?? 'medium');
        $estimatedInput = trim((string) ($_POST['estimated_completion_at'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if (!$accountId || !$itemId || $targetLevel === false) {
            Response::flash('error', 'Choose an item and enter a valid target level.');
            Response::redirect('/upgrades/planner');
        }

        $account = $this->accounts->findByIdForUser((int) $accountId, $userId);

        if ($account === null || (int) $account['is_active'] !== 1) {
            http_response_code(404);
            echo 'Clash account not found.';
            return;
        }

        $estimatedAt = null;

        if ($estimatedInput !== '') {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $estimatedInput);
            if (!$date) {
                Response::flash('error', 'Enter a valid estimated completion date.');
                Response::redirect('/upgrades/planner?account=' . (int) $accountId);
            }
            $estimatedAt = $date->format('Y-m-d H:i:s');
        }

        try {
            $this->tracking->createPlan(
                $userId,
                (int) $accountId,
                (int) $itemId,
                (int) $targetLevel,
                $priority,
                $estimatedAt,
                $notes !== '' ? mb_substr($notes, 0, 1000) : null
            );
            Response::flash('success', 'Your upgrade plan has been created.');
        } catch (RuntimeException $exception) {
            Response::flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            error_log($exception->__toString());
            Response::flash('error', 'The upgrade plan could not be created. Please try again.');
        }

        Response::redirect('/upgrades/planner?account=' . (int) $accountId);
    }

    public function updatePlan(): void
    {
        if (!$this->verifyCsrf()) {
            http_response_code(419);
            echo 'Your session expired. Refresh the page and try again.';
            return;
        }

        $userId = $this->userId();
        $accountId = filter_var($_POST['account_id'] ?? null, FILTER_VALIDATE_INT);
        $planId = filter_var($_POST['plan_id'] ?? null, FILTER_VALIDATE_INT);
        $status = (string) ($_POST['status'] ?? '');

        if (!$accountId || !$planId || !in_array($status, ['in_progress', 'cancelled'], true)) {
            Response::flash('error', 'Invalid upgrade plan action.');
            Response::redirect('/upgrades/planner');
        }

        $account = $this->accounts->findByIdForUser((int) $accountId, $userId);

        if ($account === null || (int) $account['is_active'] !== 1) {
            http_response_code(404);
            echo 'Clash account not found.';
            return;
        }

        try {
            $updated = $this->tracking->updatePlanStatus(
                (int) $planId,
                $userId,
                (int) $accountId,
                $status
            );

            Response::flash(
                $updated ? 'success' : 'error',
                $updated ? 'Upgrade plan updated.' : 'The upgrade plan could not be updated.'
            );
        } catch (Throwable $exception) {
            error_log($exception->__toString());
            Response::flash('error', 'The upgrade plan could not be updated. Please try again.');
        }

        Response::redirect('/upgrades/planner?account=' . (int) $accountId);
    }

    private function selectAccount(array $accounts): ?array
    {
        if ($accounts === []) {
            return null;
        }

        $requestedId = filter_var($_GET['account'] ?? null, FILTER_VALIDATE_INT);

        if ($requestedId) {
            foreach ($accounts as $account) {
                if ((int) $account['id'] === (int) $requestedId) {
                    return $account;
                }
            }
        }

        return $accounts[0];
    }

    private function userId(): int
    {
        $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$userId) {
            Response::redirect('/login');
        }

        return (int) $userId;
    }

    private function verifyCsrf(): bool
    {
        return Security::verifyCsrfToken($_POST['_token'] ?? null);
    }

    private function baseUrl(): string
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';

        return rtrim($config['url'], '/');
    }
}
