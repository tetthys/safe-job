<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Core\Value;

final readonly class FinalFailure
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $category = null,
    ) {
        if ($this->code === '') {
            throw new \InvalidArgumentException('FinalFailure code must not be empty.');
        }
    }
}
