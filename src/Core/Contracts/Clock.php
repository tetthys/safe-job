<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Core\Contracts;

interface Clock
{
    public function nowEpoch(): int;
}
