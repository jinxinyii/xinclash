<?php

declare(strict_types=1);

use App\Helpers\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable($root)->safeLoad();
date_default_timezone_set('Asia/Manila');

$options = getopt('', ['admin-id:', 'uses::', 'expires-days::']);
$adminId = filter_var($options['admin-id'] ?? null, FILTER_VALIDATE_INT);
$maxUses = filter_var($options['uses'] ?? 1, FILTER_VALIDATE_INT);
$expiresDays = filter_var($options['expires-days'] ?? 7, FILTER_VALIDATE_INT);

if (!$adminId || !$maxUses || $maxUses < 1 || $maxUses > 100 || $expiresDays === false || $expiresDays < 0 || $expiresDays > 365) {
    fwrite(STDERR, "Usage: php bin/create-invite.php --admin-id=1 [--uses=1] [--expires-days=7]\n");
    fwrite(STDERR, "Use --expires-days=0 for an invitation that does not expire.\n");
    exit(1);
}

$pdo = Database::connection();

$admin = $pdo->prepare("SELECT id FROM users WHERE id = :id AND role = 'admin' AND status = 'active' LIMIT 1");
$admin->execute(['id' => $adminId]);

if (!$admin->fetchColumn()) {
    fwrite(STDERR, "The supplied user ID is not an active administrator.\n");
    exit(1);
}

$rawToken = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $rawToken);
$expiresAt = $expiresDays > 0
    ? (new DateTimeImmutable())->modify('+' . $expiresDays . ' days')->format('Y-m-d H:i:s')
    : null;

$insert = $pdo->prepare(
    'INSERT INTO registration_invites (token_hash, created_by_user_id, max_uses, expires_at)
     VALUES (:token_hash, :created_by_user_id, :max_uses, :expires_at)'
);
$insert->execute([
    'token_hash' => $tokenHash,
    'created_by_user_id' => $adminId,
    'max_uses' => $maxUses,
    'expires_at' => $expiresAt,
]);

$config = require $root . '/config/app.php';
$inviteUrl = rtrim($config['url'], '/') . '/register?invite=' . $rawToken;

fwrite(STDOUT, "Invitation created. Copy this URL and share it privately:\n");
fwrite(STDOUT, $inviteUrl . PHP_EOL);
fwrite(STDOUT, "The raw invitation token is not stored in the database. Keep this URL private.\n");
