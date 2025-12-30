<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Integration\Laravel\Concerns;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Tetthys\SafeJob\Core\Contracts\Clock;
use Tetthys\SafeJob\Core\Contracts\FailurePolicy;
use Tetthys\SafeJob\Core\Contracts\Logger;
use Tetthys\SafeJob\Core\Contracts\Runner;
use Tetthys\SafeJob\Core\Contracts\SafeJob;
use Tetthys\SafeJob\Core\Contracts\Store;
use Tetthys\SafeJob\Core\Value\AttemptId;
use Tetthys\SafeJob\Integration\Laravel\Support\LaravelContext;

trait HandlesSafeJob
{
    /**
     * Implement this in your Laravel Job.
     */
    abstract protected function safeJob(): SafeJob;

    /**
     * Provide your policy (or return a default policy).
     */
    protected function failurePolicy(): FailurePolicy
    {
        return new class implements FailurePolicy {
            public function finalFailureFor(\Throwable $e): ?\Tetthys\SafeJob\Core\Value\FinalFailure
            {
                return null; // default: never finalize failure, always bubble.
            }
        };
    }

    /**
     * Laravel Job entrypoint.
     *
     * @return void
     */
    public function handle(Runner $runner, Store $store, Clock $clock, Logger $logger): void
    {
        // AttemptId: stable per queue attempt, best-effort.
        $attempt = method_exists($this, 'attempts')
            ? (string) $this->attempts()
            : '0';

        $attemptId = new AttemptId('laravel:' . $attempt . ':' . (string) Str::uuid());

        $ctx = new LaravelContext(
            attemptId: $attemptId,
            store: $store,
            failurePolicy: $this->failurePolicy(),
            clock: $clock,
            logger: $logger,
        );

        $ttl = (int) config('tetthys-safejob.lease_ttl_seconds', 30);

        $runner->handle($this->safeJob(), $ctx, $ttl);
    }
}
