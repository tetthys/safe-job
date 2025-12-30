<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Core\Contracts;

use Tetthys\SafeJob\Core\Value\Outcome;

/**
 * Financial-grade job contract:
 * - perform(): retry-safe work
 * - onSucceededOnce(): success-only, exactly-once side effects
 */
interface SafeJob
{
    /** Business-unique (not per attempt). */
    public function key(): IdempotencyKey;

    /**
     * Retry-safe work.
     * Must be safe to run multiple times.
     */
    public function perform(Context $ctx): Outcome;

    /**
     * Called only after finalizeSuccess() succeeds (exactly once globally).
     * Put irreversible success-only actions here.
     */
    public function onSucceededOnce(Context $ctx): void;
}
