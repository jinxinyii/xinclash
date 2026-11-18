<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\Security;
use App\Models\GameAccount;
use App\Services\PlayerSyncService;
use App\Services\PlayerVerificationService;
use App\Services\SupercellApiException;
use App\Services\SupercellApiService;
use App\Services\UpgradeTrackingService;
use PDO;
use PDOException;
use Throwable;

final class GameAccountController
{
    private GameAccount $accounts;
    private SupercellApiService $api;

    public function __construct(private readonly PDO $pdo)
    {
        $this->accounts = new GameAccount($pdo);
        $this->api = new SupercellApiService();
    }

    public function index(): void
    {
        $userId = $this->userId();

        Response::render('Accounts/index', [
            'baseUrl' => $this->baseUrl(),
            'csrfToken' => Security::csrfToken(),
            'accounts' => $this->accounts->findAllByUser($userId),
            'success' => Response::flash('success'),
            'error' => Response::flash('error'),
        ]);
    }

    public function showAdd(): void
    {
        Response::render('Accounts/add', [
            'baseUrl' => $this->baseUrl(),
            'csrfToken' => Security::csrfToken(),
            'error' => Response::flash('error'),
            'oldTag' => '',
        ]);
    }

    public function store(): void
    {
        if (!$this->verifyCsrf()) {
            http_response_code(419);
            Response::render('Accounts/add', [
                'baseUrl' => $this->baseUrl(),
                'csrfToken' => Security::csrfToken(),
                'error' => 'Your session expired. Refresh the page and try again.',
                'oldTag' => '',
            ]);
            return;
        }

        $userId = $this->userId();
        $tagInput = trim((string) ($_POST['player_tag'] ?? ''));
        $playerToken = trim((string) ($_POST['player_token'] ?? ''));

        try {
            $playerTag = $this->normalizePlayerTag($tagInput);

            if ($playerToken === '' || strlen($playerToken) > 128) {
                throw new \InvalidArgumentException('Enter the API token shown in your Clash of Clans game settings.');
            }

            $profile = $this->api->getPlayer($playerTag);

            $returnedTag = $this->normalizePlayerTag((string) ($profile['tag'] ?? ''));

            if ($returnedTag !== $playerTag) {
                throw new \RuntimeException('The API returned a different player tag. Please check the tag and try again.');
            }

            $verification = new PlayerVerificationService($this->api);

            if (!$verification->verify($playerTag, $playerToken)) {
                throw new \RuntimeException('The in-game API token did not verify ownership of this player account.');
            }

            $this->pdo->beginTransaction();

            $accountId = $this->accounts->createVerified($userId, $playerTag, $profile);
            $this->accounts->saveSnapshot($accountId, $userId, $profile);

            $tracking = new UpgradeTrackingService($this->pdo);
            $summary = $tracking->synchronizeApiItems($userId, $accountId, $profile);

            $this->pdo->commit();

            unset($playerToken);
            Response::flash(
                'success',
                sprintf(
                    'Your account is connected and verified. Imported %d upgrade items.',
                    (int) $summary['items_processed']
                )
            );
            Response::redirect('/accounts/show?id=' . $accountId);
        } catch (\InvalidArgumentException $exception) {
            $this->renderAddError($exception->getMessage(), $tagInput);
        } catch (SupercellApiException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $this->renderAddError($exception->getMessage(), $tagInput, $exception->httpStatus() ?: 422);
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($exception->getCode() === '23000') {
                $this->renderAddError('This player account is already connected to your xinClash account.', $tagInput, 409);
                return;
            }

            error_log($exception->__toString());
            $this->renderAddError('The player account could not be saved. Please try again.', $tagInput, 500);
        } catch (\RuntimeException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $this->renderAddError($exception->getMessage(), $tagInput, 422);
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log($exception->__toString());
            $this->renderAddError('The account could not be connected. Check the server logs and try again.', $tagInput, 500);
        } finally {
            unset($playerToken);
        }
    }

    public function show(): void
    {
        $userId = $this->userId();
        $accountId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $account = $accountId ? $this->accounts->findByIdForUser((int) $accountId, $userId) : null;

        if ($account === null) {
            http_response_code(404);
            echo 'Clash account not found.';
            return;
        }

        Response::render('Accounts/show', [
            'baseUrl' => $this->baseUrl(),
            'csrfToken' => Security::csrfToken(),
            'account' => $account,
            'success' => Response::flash('success'),
            'error' => Response::flash('error'),
        ]);
    }

    public function sync(): void
    {
        if (!$this->verifyCsrf()) {
            http_response_code(419);
            echo 'Your session expired. Refresh the page and try again.';
            return;
        }

        $userId = $this->userId();
        $accountId = filter_var($_POST['account_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$accountId) {
            Response::flash('error', 'Select a valid Clash account.');
            Response::redirect('/accounts');
        }

        try {
            $sync = new PlayerSyncService($this->pdo, $this->api);
            $result = $sync->sync((int) $accountId, $userId);
            $summary = $result['summary'];

            Response::flash(
                'success',
                sprintf(
                    'Profile synchronized: %d items checked, %d level changes detected.',
                    (int) $summary['items_processed'],
                    (int) $summary['levels_changed']
                )
            );
            Response::redirect('/accounts/show?id=' . (int) $accountId);
        } catch (Throwable $exception) {
            error_log($exception->__toString());

            $message = $exception instanceof SupercellApiException
                ? $exception->getMessage()
                : 'Synchronization failed. Check the server logs and try again.';

            Response::flash('error', $message);
            Response::redirect('/accounts/show?id=' . (int) $accountId);
        }
    }

    private function normalizePlayerTag(string $tag): string
    {
        $tag = strtoupper(trim($tag));
        $tag = ltrim($tag, '#');

        if (!preg_match('/^[0289PYLQGRJCUV]{3,14}$/', $tag)) {
            throw new \InvalidArgumentException('Enter a valid player tag using the characters 0, 2, 8, 9, P, Y, L, Q, G, R, J, C, U, and V.');
        }

        return '#' . $tag;
    }

    private function renderAddError(string $message, string $tag, int $status = 422): void
    {
        http_response_code($status);

        Response::render('Accounts/add', [
            'baseUrl' => $this->baseUrl(),
            'csrfToken' => Security::csrfToken(),
            'error' => $message,
            'oldTag' => $tag,
        ]);
    }

    private function verifyCsrf(): bool
    {
        return Security::verifyCsrfToken($_POST['_token'] ?? null);
    }

    private function userId(): int
    {
        $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$userId) {
            Response::redirect('/login');
        }

        return (int) $userId;
    }

    private function baseUrl(): string
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';

        return rtrim($config['url'], '/');
    }
}
