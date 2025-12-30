<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Core\Value;

final readonly class Outcome
{
    private function __construct(
        public bool $didRun,
        public bool $succeeded,
        public ?FinalFailure $finalFailure,
    ) {}

    public static function ranSuccess(): self
    {
        return new self(true, true, null);
    }

    public static function ranFinalFailure(FinalFailure $failure): self
    {
        return new self(true, false, $failure);
    }

    public static function noop(): self
    {
        return new self(false, true, null);
    }
}
