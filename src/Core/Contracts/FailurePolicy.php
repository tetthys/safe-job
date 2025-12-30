<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Core\Contracts;

use Tetthys\SafeJob\Core\Value\FinalFailure;

interface FailurePolicy
{
    /**
     * Return FinalFailure only when this exception is allowed to finalize FAILED.
     * Return null => must NOT finalize failure (should be retried / bubbled up).
     */
    public function finalFailureFor(\Throwable $e): ?FinalFailure;
}
