<?php

declare(strict_types=1);

namespace App\Helpers;

use DateTimeImmutable;

final class Validator
{
    private static function length(string $value): int
    {
        $count = preg_match_all('/./us', $value, $matches);

        return $count === false ? PHP_INT_MAX : $count;
    }

    public static function registration(array $input): array
    {
        $errors = [];

        $firstName = trim((string) ($input['first_name'] ?? ''));
        $middleInitial = trim((string) ($input['middle_initial'] ?? ''));
        $lastName = trim((string) ($input['last_name'] ?? ''));
        $birthdate = trim((string) ($input['birthdate'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');
        $passwordConfirmation = (string) ($input['password_confirmation'] ?? '');
        $inviteToken = trim((string) ($input['invite_token'] ?? ''));

        if ($firstName === '' || self::length($firstName) > 100) {
            $errors['first_name'] = 'Enter a first name (up to 100 characters).';
        }

        if (self::length($middleInitial) > 5) {
            $errors['middle_initial'] = 'Middle initial must be 5 characters or fewer.';
        }

        if ($lastName === '' || self::length($lastName) > 100) {
            $errors['last_name'] = 'Enter a last name (up to 100 characters).';
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $birthdate);
        $dateErrors = DateTimeImmutable::getLastErrors();
        $validDate = $date !== false
            && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
            && $date->format('Y-m-d') === $birthdate
            && $date <= new DateTimeImmutable('today');

        if (!$validDate) {
            $errors['birthdate'] = 'Enter a valid birthdate that is not in the future.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if (strlen($password) < 12) {
            $errors['password'] = 'Your password must contain at least 12 characters.';
        } elseif (strlen($password) > 4096) {
            $errors['password'] = 'Your password is too long.';
        }

        if (!hash_equals($password, $passwordConfirmation)) {
            $errors['password_confirmation'] = 'The password confirmation does not match.';
        }

        if (!preg_match('/^[a-fA-F0-9]{64}$/', $inviteToken)) {
            $errors['invite_token'] = 'A valid invitation token is required.';
        }

        return [
            'errors' => $errors,
            'data' => [
                'first_name' => $firstName,
                'middle_initial' => $middleInitial !== '' ? $middleInitial : null,
                'last_name' => $lastName,
                'birthdate' => $birthdate,
                'email' => $email,
                'password' => $password,
                'invite_token' => $inviteToken,
            ],
        ];
    }
}
