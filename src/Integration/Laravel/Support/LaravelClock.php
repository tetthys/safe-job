<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Integration\Laravel\Support;

use Tetthys\SafeJob\Core\Contracts\Clock;

final readonly class LaravelClock implements Clock
{
    public function nowEpoch(): int
    {
        return time();
    }
}
