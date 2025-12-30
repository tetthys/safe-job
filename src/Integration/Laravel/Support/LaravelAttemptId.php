<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Integration\Laravel\Support;

use Tetthys\SafeJob\Core\Value\AttemptId;

final class LaravelAttemptId
{
    public static function new(): AttemptId
    {
        // Use cryptographically secure random id
        $bytes = random_bytes(16);
        return new AttemptId(bin2hex($bytes));
    }
}
