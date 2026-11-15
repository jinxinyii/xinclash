<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'xinClash',
    'environment' => $_ENV['APP_ENV'] ?? 'local',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'),
    'key' => $_ENV['APP_KEY'] ?? '',
];
