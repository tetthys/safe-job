<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Integration\Laravel\Support;

use Tetthys\SafeJob\Core\Contracts\Clock;
use Tetthys\SafeJob\Core\Contracts\Context;
use Tetthys\SafeJob\Core\Contracts\FailurePolicy;
use Tetthys\SafeJob\Core\Contracts\Logger;
use Tetthys\SafeJob\Core\Contracts\Store;
use Tetthys\SafeJob\Core\Value\AttemptId;

final readonly class LaravelContext implements Context
{
    public function __construct(
        private AttemptId $attemptId,
        private Store $store,
        private FailurePolicy $failurePolicy,
        private Clock $clock,
        private Logger $logger,
    ) {}

    public function attemptId(): AttemptId
    {
        return $this->attemptId;
    }

    public function store(): Store
    {
        return $this->store;
    }

    public function failurePolicy(): FailurePolicy
    {
        return $this->failurePolicy;
    }

    public function clock(): Clock
    {
        return $this->clock;
    }

    public function logger(): Logger
    {
        return $this->logger;
    }
}
