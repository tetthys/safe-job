<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Core\Contracts;

use Tetthys\SafeJob\Core\Value\AttemptId;
use Tetthys\SafeJob\Core\Value\Lease;
use Tetthys\SafeJob\Core\Value\State;
use Tetthys\SafeJob\Core\Value\FinalFailure;

interface Store
{
    public function state(IdempotencyKey $key): State;

    /**
     * Acquire exclusive lease atomically.
     * - NEW -> RUNNING (lease issued)
     * - RUNNING -> RUNNING only if expired (impl-defined)
     * - SUCCEEDED/FAILED -> null
     */
    public function tryLease(
        IdempotencyKey $key,
        AttemptId $attemptId,
        int $ttlSeconds
    ): ?Lease;

    /** Optional for long-running jobs. Must validate token ownership. */
    public function extendLease(IdempotencyKey $key, Lease $lease, int $ttlSeconds): void;

    /**
     * Finalize success exactly once.
     * Must throw if already finalized or lease not held.
     */
    public function finalizeSuccess(IdempotencyKey $key, Lease $lease, array $meta = []): void;

    /**
     * Finalize failure exactly once (only when FailurePolicy allows).
     */
    public function finalizeFailure(
        IdempotencyKey $key,
        Lease $lease,
        FinalFailure $failure,
        array $meta = []
    ): void;
}
