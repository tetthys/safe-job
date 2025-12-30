<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Core\Value;

final readonly class Lease
{
    public function __construct(
        public string $token,
        public int $expiresAtEpoch,
    ) {
        if ($this->token === '') {
            throw new \InvalidArgumentException('Lease token must not be empty.');
        }
    }
}
