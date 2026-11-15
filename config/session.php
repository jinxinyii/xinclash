<?php

declare(strict_types=1);

return [
    'name' => 'xinClash_session',
    'lifetime' => 0,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
];
