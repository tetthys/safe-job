<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Integration\Laravel\Support;

use Illuminate\Support\Facades\DB;
use Tetthys\SafeJob\Core\Contracts\Exceptions as CoreExceptions;
use Tetthys\SafeJob\Core\Contracts\IdempotencyKey;
use Tetthys\SafeJob\Core\Contracts\Store;
use Tetthys\SafeJob\Core\Value\AttemptId;
use Tetthys\SafeJob\Core\Value\FinalFailure;
use Tetthys\SafeJob\Core\Value\Lease;
use Tetthys\SafeJob\Core\Value\State;

final readonly class DbStore implements Store
{
    public function __construct(
        private string $table,
        private bool $allowStealExpiredLease,
    ) {}

    public function state(IdempotencyKey $key): State
    {
        $row = DB::table($this->table)->where('key', (string) $key)->first();

        if ($row === null) {
            return State::NEW;
        }

        return State::from((string) $row->state);
    }

    public function tryLease(IdempotencyKey $key, AttemptId $attemptId, int $ttlSeconds): ?Lease
    {
        $now = time();
        $expiresAt = $now + max(1, $ttlSeconds);
        $token = bin2hex(random_bytes(16));

        return DB::transaction(function () use ($key, $attemptId, $now, $expiresAt, $token): ?Lease {
            $row = DB::table($this->table)
                ->where('key', (string) $key)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                DB::table($this->table)->insert([
                    'key' => (string) $key,
                    'state' => State::RUNNING->value,
                    'attempt_id' => $attemptId->value,
                    'lease_token' => $token,
                    'lease_expires_at_epoch' => $expiresAt,
                    'meta' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return new Lease($token, $expiresAt);
            }

            $state = State::from((string) $row->state);

            if ($state === State::SUCCEEDED || $state === State::FAILED) {
                return null;
            }

            // RUNNING lease check
            $currentExp = (int) ($row->lease_expires_at_epoch ?? 0);
            $isExpired = $currentExp > 0 && $currentExp <= $now;

            if ($state === State::RUNNING) {
                if (! $isExpired) {
                    return null;
                }
                if (! $this->allowStealExpiredLease) {
                    return null;
                }
            }

            // NEW or expired RUNNING -> issue new lease
            DB::table($this->table)
                ->where('key', (string) $key)
                ->update([
                    'state' => State::RUNNING->value,
                    'attempt_id' => $attemptId->value,
                    'lease_token' => $token,
                    'lease_expires_at_epoch' => $expiresAt,
                    'updated_at' => now(),
                ]);

            return new Lease($token, $expiresAt);
        });
    }

    public function extendLease(IdempotencyKey $key, Lease $lease, int $ttlSeconds): void
    {
        $now = time();
        $expiresAt = $now + max(1, $ttlSeconds);

        $updated = DB::table($this->table)
            ->where('key', (string) $key)
            ->where('state', State::RUNNING->value)
            ->where('lease_token', $lease->token)
            ->update([
                'lease_expires_at_epoch' => $expiresAt,
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new \RuntimeException(CoreExceptions::LEASE_NOT_HELD);
        }
    }

    public function finalizeSuccess(IdempotencyKey $key, Lease $lease, array $meta = []): void
    {
        $updated = DB::table($this->table)
            ->where('key', (string) $key)
            ->where('state', State::RUNNING->value)
            ->where('lease_token', $lease->token)
            ->update([
                'state' => State::SUCCEEDED->value,
                'succeeded_at' => now(),
                'lease_token' => null,
                'lease_expires_at_epoch' => null,
                'meta' => $meta === [] ? DB::raw('meta') : json_encode($meta, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            $this->throwFinalizeError($key, $lease);
        }
    }

    public function finalizeFailure(IdempotencyKey $key, Lease $lease, FinalFailure $failure, array $meta = []): void
    {
        $updated = DB::table($this->table)
            ->where('key', (string) $key)
            ->where('state', State::RUNNING->value)
            ->where('lease_token', $lease->token)
            ->update([
                'state' => State::FAILED->value,
                'failed_at' => now(),
                'failure_code' => $failure->code,
                'failure_message' => $failure->message,
                'failure_category' => $failure->category,
                'lease_token' => null,
                'lease_expires_at_epoch' => null,
                'meta' => $meta === [] ? DB::raw('meta') : json_encode($meta, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            $this->throwFinalizeError($key, $lease);
        }
    }

    private function throwFinalizeError(IdempotencyKey $key, Lease $lease): never
    {
        $row = DB::table($this->table)->where('key', (string) $key)->first();

        if ($row === null) {
            throw new \RuntimeException(CoreExceptions::INVARIANT_VIOLATION);
        }

        $state = (string) $row->state;

        if ($state === State::SUCCEEDED->value || $state === State::FAILED->value) {
            throw new \RuntimeException(CoreExceptions::ALREADY_FINALIZED);
        }

        // Most likely lease token mismatch or lost lease
        throw new \RuntimeException(CoreExceptions::LEASE_NOT_HELD);
    }
}
