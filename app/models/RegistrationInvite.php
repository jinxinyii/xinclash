<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use PDO;
use RuntimeException;

final class RegistrationInvite
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function isUsable(string $rawToken): bool
    {
        if (!preg_match('/^[a-fA-F0-9]{64}$/', $rawToken)) {
            return false;
        }

        $statement = $this->pdo->prepare(
            'SELECT id, max_uses, uses_count, expires_at, revoked_at
             FROM registration_invites
             WHERE token_hash = :token_hash
             LIMIT 1'
        );
        $statement->execute(['token_hash' => hash('sha256', $rawToken)]);
        $invite = $statement->fetch();

        return $invite !== false && $this->inviteRowIsUsable($invite);
    }

    public function consume(string $rawToken): void
    {
        if (!preg_match('/^[a-fA-F0-9]{64}$/', $rawToken)) {
            throw new RuntimeException('The invitation is invalid or has expired.');
        }

        $statement = $this->pdo->prepare(
            'SELECT id, max_uses, uses_count, expires_at, revoked_at
             FROM registration_invites
             WHERE token_hash = :token_hash
             LIMIT 1
             FOR UPDATE'
        );
        $statement->execute(['token_hash' => hash('sha256', $rawToken)]);
        $invite = $statement->fetch();

        if ($invite === false || !$this->inviteRowIsUsable($invite)) {
            throw new RuntimeException('The invitation is invalid or has expired.');
        }

        $update = $this->pdo->prepare(
            'UPDATE registration_invites
             SET uses_count = uses_count + 1
             WHERE id = :id AND uses_count < max_uses'
        );
        $update->execute(['id' => $invite['id']]);

        if ($update->rowCount() !== 1) {
            throw new RuntimeException('The invitation has already been used.');
        }
    }

    private function inviteRowIsUsable(array $invite): bool
    {
        if ($invite['revoked_at'] !== null || (int) $invite['uses_count'] >= (int) $invite['max_uses']) {
            return false;
        }

        if ($invite['expires_at'] !== null) {
            return new DateTimeImmutable($invite['expires_at']) > new DateTimeImmutable();
        }

        return true;
    }
}
