<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Core\Contracts;

use Tetthys\SafeJob\Core\Value\Outcome;

interface Runner
{
    /**
     * Semantics:
     * - if state == SUCCEEDED => Outcome::noop()
     * - if cannot lease => Outcome::noop()
     * - try perform():
     *   - success => finalizeSuccess(); then onSucceededOnce()
     *   - exception =>
     *       - failurePolicy->finalFailureFor(e) != null => finalizeFailure(); return final-failed
     *       - else rethrow (do not finalize)
     */
    public function handle(SafeJob $job, Context $ctx, int $leaseTtlSeconds = 30): Outcome;
}
