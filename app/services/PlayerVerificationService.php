<?php

declare(strict_types=1);

namespace App\Services;

final class PlayerVerificationService
{
    public function __construct(private readonly SupercellApiService $api)
    {
    }

    public function verify(string $playerTag, string $playerToken): bool
    {
        if ($playerToken === '') {
            return false;
        }

        return $this->api->verifyPlayerToken($playerTag, $playerToken);
    }
}
