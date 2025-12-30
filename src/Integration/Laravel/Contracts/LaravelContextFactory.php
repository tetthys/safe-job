<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Integration\Laravel\Contracts;

use Tetthys\SafeJob\Core\Contracts\Context;
use Tetthys\SafeJob\Core\Contracts\Store;
use Tetthys\SafeJob\Core\Contracts\FailurePolicy;

interface LaravelContextFactory
{
    /**
     * Create a Core Context for current Laravel job execution.
     * - attemptId should be unique per execution attempt.
     */
    public function make(Store $store, FailurePolicy $failurePolicy): Context;
}
