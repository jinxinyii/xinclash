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

$pdo = Database::connection();

$adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

if ($adminCount > 0) {
    fwrite(STDERR, "An administrator already exists. This bootstrap command can only create the first administrator.\n");
    exit(1);
}

$read = static function (string $label): string {
    fwrite(STDOUT, $label . ': ');
    return trim((string) fgets(STDIN));
};

$firstName = $read('First name');
$middleInitial = $read('Middle initial (optional)');
$lastName = $read('Last name');
$birthdate = $read('Birthdate (YYYY-MM-DD)');
$email = strtolower($read('Email'));
fwrite(STDOUT, 'Password (input will be visible): ');
$password = rtrim((string) fgets(STDIN), "\r\n");

$date = DateTimeImmutable::createFromFormat('!Y-m-d', $birthdate);
$dateErrors = DateTimeImmutable::getLastErrors();
$validDate = $date !== false
    && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
    && $date->format('Y-m-d') === $birthdate
    && $date <= new DateTimeImmutable('today');

if ($firstName === '' || strlen($firstName) > 400
    || strlen($middleInitial) > 20
    || $lastName === '' || strlen($lastName) > 400
    || !$validDate
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($email) > 254
    || strlen($password) < 12
    || strlen($password) > 4096) {
    fwrite(STDERR, "Invalid input. Check the names, birthdate, email, and password (minimum 12 characters).\n");
    exit(1);
}

$statement = $pdo->prepare(
    "INSERT INTO users
        (first_name, middle_initial, last_name, birthdate, email, password_hash, role, status)
     VALUES
        (:first_name, :middle_initial, :last_name, :birthdate, :email, :password_hash, 'admin', 'active')"
);

try {
    $statement->execute([
        'first_name' => $firstName,
        'middle_initial' => $middleInitial !== '' ? $middleInitial : null,
        'last_name' => $lastName,
        'birthdate' => $birthdate,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
} catch (PDOException $exception) {
    fwrite(STDERR, "Could not create the administrator. Check whether the email is already registered.\n");
    exit(1);
}

fwrite(STDOUT, 'Administrator created successfully. User ID: ' . $pdo->lastInsertId() . PHP_EOL);
