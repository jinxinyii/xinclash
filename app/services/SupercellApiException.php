<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class SupercellApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $httpStatus = 0
    ) {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
