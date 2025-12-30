<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Integration\Laravel\Support;

use Tetthys\SafeJob\Integration\Laravel\Contracts\LaravelContextFactory;
use Tetthys\SafeJob\Core\Contracts\Context;
use Tetthys\SafeJob\Core\Contracts\Store;
use Tetthys\SafeJob\Core\Contracts\FailurePolicy;
use Tetthys\SafeJob\Core\Contracts\Clock;
use Tetthys\SafeJob\Core\Contracts\Logger;

final readonly class DefaultLaravelContextFactory implements LaravelContextFactory
{
    public function __construct(
        private Clock $clock,
        private Logger $logger,
    ) {}

    public function make(Store $store, FailurePolicy $failurePolicy): Context
    {
        return new LaravelContext(
            attemptId: LaravelAttemptId::new(),
            store: $store,
            failurePolicy: $failurePolicy,
            clock: $this->clock,
            logger: $this->logger,
        );
    }
}
