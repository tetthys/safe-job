<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Integration\Laravel\Support;

use Tetthys\SafeJob\Core\Contracts\Context;
use Tetthys\SafeJob\Core\Contracts\Runner;
use Tetthys\SafeJob\Core\Contracts\SafeJob;
use Tetthys\SafeJob\Core\Value\Outcome;
use Tetthys\SafeJob\Core\Value\State;

final class DbRunner implements Runner
{
    public function handle(SafeJob $job, Context $ctx, int $leaseTtlSeconds = 30): Outcome
    {
        $key = $job->key();
        $store = $ctx->store();

        $state = $store->state($key);
        if ($state === State::SUCCEEDED) {
            return Outcome::noop();
        }
        if ($state === State::FAILED) {
            // already final failed: do not rerun
            return Outcome::noop();
        }

        $lease = $store->tryLease($key, $ctx->attemptId(), $leaseTtlSeconds);
        if ($lease === null) {
            return Outcome::noop();
        }

        try {
            $result = $job->perform($ctx);

            // perform() may return noop if it detected work already done.
            // Still we finalize success to close the lifecycle exactly once.
            if ($result->succeeded) {
                $store->finalizeSuccess($key, $lease, meta: [
                    'attempt_id' => $ctx->attemptId()->value,
                ]);

                // Exactly-once success side effects
                $job->onSucceededOnce($ctx);

                return Outcome::ranSuccess();
            }

            // If perform explicitly reported final failure (rare),
            // treat it as finalizable only if policy allows.
            if ($result->finalFailure !== null) {
                $final = $ctx->failurePolicy()->finalFailureFor(new \RuntimeException($result->finalFailure->code));
                $final ??= $result->finalFailure;

                $store->finalizeFailure($key, $lease, $final, meta: [
                    'attempt_id' => $ctx->attemptId()->value,
                ]);

                return Outcome::ranFinalFailure($final);
            }

            // If reached here, treat as success.
            $store->finalizeSuccess($key, $lease, meta: [
                'attempt_id' => $ctx->attemptId()->value,
            ]);
            $job->onSucceededOnce($ctx);

            return Outcome::ranSuccess();
        } catch (\Throwable $e) {
            $final = $ctx->failurePolicy()->finalFailureFor($e);

            if ($final !== null) {
                $store->finalizeFailure($key, $lease, $final, meta: [
                    'attempt_id' => $ctx->attemptId()->value,
                    'exception' => get_class($e),
                ]);

                return Outcome::ranFinalFailure($final);
            }

            // Not finalizable => bubble up (Laravel will retry/fail job normally)
            throw $e;
        }
    }
}
