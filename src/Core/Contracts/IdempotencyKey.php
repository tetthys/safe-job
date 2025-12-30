<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Core\Contracts;

final readonly class IdempotencyKey
{
    public function __construct(public string $value)
    {
        if ($this->value === '') {
            throw new \InvalidArgumentException('IdempotencyKey must not be empty.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
