<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\Security;
use App\Helpers\Validator;
use App\Models\RegistrationInvite;
use App\Models\User;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class AuthController
{
    private User $users;
    private RegistrationInvite $invites;

    public function __construct(private readonly PDO $pdo)
    {
        $this->users = new User($pdo);
        $this->invites = new RegistrationInvite($pdo);
    }

    public function showLogin(): void
    {
        if (isset($_SESSION['user_id'])) {
            Response::redirect('/dashboard');
        }

        Response::render('Auth/login', [
            'csrfToken' => Security::csrfToken(),
            'error' => Response::flash('error'),
            'success' => Response::flash('success'),
            'old' => [],
        ]);
    }

    public function login(): void
    {
        if (!Security::verifyCsrfToken($_POST['_token'] ?? null)) {
            http_response_code(419);
            Response::render('Auth/login', [
                'csrfToken' => Security::csrfToken(),
                'error' => 'Your session expired. Refresh the page and try again.',
                'success' => null,
                'old' => ['email' => trim((string) ($_POST['email'] ?? ''))],
            ]);
            return;
        }

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $this->renderLoginError('The email or password is incorrect.', $email);
            return;
        }

        try {
            $ipHash = Security::ipHash();
        } catch (Throwable $exception) {
            error_log($exception->__toString());
            $this->renderLoginError('Authentication is temporarily unavailable.', $email, 500);
            return;
        }

        $attempts = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM login_attempts
             WHERE email = :email
               AND ip_hash = :ip_hash
               AND was_successful = 0
               AND attempted_at >= (CURRENT_TIMESTAMP - INTERVAL 15 MINUTE)'
        );
        $attempts->execute([
            'email' => $email,
            'ip_hash' => $ipHash,
        ]);

        if ((int) $attempts->fetchColumn() >= 5) {
            $this->renderLoginError('Too many unsuccessful attempts. Please wait 15 minutes before trying again.', $email, 429);
            return;
        }

        $user = $this->users->findByEmail($email);
        $validPassword = $user !== null && password_verify($password, $user['password_hash']);
        $isActive = $user !== null && $user['status'] === 'active';

        $this->recordLoginAttempt($email, $ipHash, $validPassword && $isActive);

        if (!$validPassword || !$isActive) {
            $this->renderLoginError('The email or password is incorrect, or the account is unavailable.', $email);
            return;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash(
                (int) $user['id'],
                password_hash($password, PASSWORD_DEFAULT)
            );
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
        $_SESSION['user_role'] = $user['role'];
        unset($_SESSION['_csrf_token']);

        $this->users->updateLastLogin((int) $user['id']);

        Response::redirect('/dashboard');
    }

    public function showRegister(): void
    {
        if (isset($_SESSION['user_id'])) {
            Response::redirect('/dashboard');
        }

        $inviteToken = trim((string) ($_GET['invite'] ?? ''));

        Response::render('Auth/register', [
            'csrfToken' => Security::csrfToken(),
            'error' => Response::flash('error'),
            'inviteToken' => $inviteToken,
            'errors' => [],
            'old' => [],
        ]);
    }

    public function register(): void
    {
        if (!Security::verifyCsrfToken($_POST['_token'] ?? null)) {
            http_response_code(419);
            $this->renderRegisterError(
                'Your session expired. Refresh the page and try again.',
                [],
                trim((string) ($_POST['invite_token'] ?? ''))
            );
            return;
        }

        $validation = Validator::registration($_POST);
        $data = $validation['data'];
        $errors = $validation['errors'];

        if ($errors !== []) {
            $this->renderRegisterError('Please correct the highlighted fields.', $errors, $data['invite_token'], $data);
            return;
        }

        if ($this->users->emailExists($data['email'])) {
            $errors['email'] = 'An account with this email already exists.';
            $this->renderRegisterError('Please correct the highlighted fields.', $errors, $data['invite_token'], $data);
            return;
        }

        try {
            $this->pdo->beginTransaction();
            $this->invites->consume($data['invite_token']);
            $userId = $this->users->create($data);
            $this->pdo->commit();

            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = trim($data['first_name'] . ' ' . $data['last_name']);
            $_SESSION['user_role'] = 'member';
            unset($_SESSION['_csrf_token']);

            Response::redirect('/dashboard');
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($exception->getCode() === '23000') {
                $errors['email'] = 'An account with this email already exists.';
                $this->renderRegisterError('Please correct the highlighted fields.', $errors, $data['invite_token'], $data);
                return;
            }

            error_log($exception->__toString());
            $this->renderRegisterError('Registration could not be completed. Please try again.', [], $data['invite_token'], $data, 500);
        } catch (RuntimeException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $this->renderRegisterError($exception->getMessage(), [], $data['invite_token'], $data);
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log($exception->__toString());
            $this->renderRegisterError('Registration could not be completed. Please try again.', [], $data['invite_token'], $data, 500);
        }
    }

    public function logout(): void
    {
        if (!Security::verifyCsrfToken($_POST['_token'] ?? null)) {
            http_response_code(419);
            echo 'Your session expired. Refresh the page and try again.';
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
        Response::redirect('/login');
    }

    private function recordLoginAttempt(string $email, string $ipHash, bool $successful): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO login_attempts (email, ip_hash, was_successful)
             VALUES (:email, :ip_hash, :was_successful)'
        );
        $statement->execute([
            'email' => $email,
            'ip_hash' => $ipHash,
            'was_successful' => $successful ? 1 : 0,
        ]);
    }

    private function renderLoginError(string $message, string $email, int $status = 422): void
    {
        http_response_code($status);
        Response::render('Auth/login', [
            'csrfToken' => Security::csrfToken(),
            'error' => $message,
            'success' => null,
            'old' => ['email' => $email],
        ]);
    }

    private function renderRegisterError(
        string $message,
        array $errors,
        string $inviteToken,
        array $old = [],
        int $status = 422
    ): void {
        http_response_code($status);
        Response::render('Auth/register', [
            'csrfToken' => Security::csrfToken(),
            'error' => $message,
            'inviteToken' => $inviteToken,
            'errors' => $errors,
            'old' => $old,
        ]);
    }
}
