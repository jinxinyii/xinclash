<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class User
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, first_name, middle_initial, last_name, email, password_hash, role, status
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);

        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findActiveById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT id, first_name, middle_initial, last_name, email, role, status
             FROM users
             WHERE id = :id AND status = 'active'
             LIMIT 1"
        );
        $statement->execute(['id' => $id]);

        $user = $statement->fetch();

        return $user ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO users
                (first_name, middle_initial, last_name, birthdate, email, password_hash, role, status)
             VALUES
                (:first_name, :middle_initial, :last_name, :birthdate, :email, :password_hash, 'member', 'active')"
        );

        $statement->execute([
            'first_name' => $data['first_name'],
            'middle_initial' => $data['middle_initial'],
            'last_name' => $data['last_name'],
            'birthdate' => $data['birthdate'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateLastLogin(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id'
        );
        $statement->execute([
            'password_hash' => $hash,
            'id' => $id,
        ]);
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);

        return (bool) $statement->fetchColumn();
    }
}
