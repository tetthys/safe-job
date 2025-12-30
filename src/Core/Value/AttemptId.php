<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Core\Value;

final readonly class AttemptId
{
    public function __construct(public string $value)
    {
        if ($this->value === '') {
            throw new \InvalidArgumentException('AttemptId must not be empty.');
        }
    }
}
