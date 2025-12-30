# Tetthys SafeJob

**Tetthys SafeJob** is a financial-grade job execution framework that guarantees  
**exactly-once success semantics** even under retries, duplicate dispatches, or worker crashes.

It is designed for high-risk domains such as payments, wallets, settlements, and external side effects,
and integrates naturally with **Laravel 12 Queue** while remaining framework-agnostic at the core.

---

## Why SafeJob?

Laravel jobs are *at-least-once* by default.

This means:

- A job **may run more than once**
- `handle()` may be re-executed after partial success
- Side effects (payments, events, notifications) can be duplicated

SafeJob solves this by **splitting execution into two strictly separated phases**:

1. **Retry-safe work** (can run many times)
2. **Exactly-once success effects** (runs globally only once)

---

## Execution Pipeline

Every SafeJob follows the same fixed pipeline:

```

┌────────────────────┐
│ tryLease()         │  Acquire global execution right
│ (DB row + TTL)     │
└─────────┬──────────┘
│
▼
┌────────────────────┐
│ perform()          │  Retry-safe, idempotent work
│ (may run many)     │
└─────────┬──────────┘
│
▼
┌────────────────────┐
│ finalizeSuccess()  │  Atomically mark SUCCEEDED
│ (exactly once)     │
└─────────┬──────────┘
│
▼
┌────────────────────┐
│ onSucceededOnce()  │  Irreversible side effects
│ (exactly once)     │
└────────────────────┘

````

If an exception occurs:

- If allowed by `FailurePolicy` → finalize as **FAILED**
- Otherwise → exception is rethrown and normal Laravel retry applies

---

## Installation

```bash
composer require tetthys/safejob
php artisan migrate
````

This will:

* Register the Laravel service provider automatically
* Install the `safe_jobs` table for lease & state tracking

---

## Core Concepts

### SafeJob (Business Logic)

You implement **SafeJob**, not Laravel Job logic.

```php
use Tetthys\SafeJob\Core\Contracts\SafeJob;
use Tetthys\SafeJob\Core\Contracts\Context;
use Tetthys\SafeJob\Core\Contracts\IdempotencyKey;
use Tetthys\SafeJob\Core\Value\Outcome;

final class ChargeWalletSafeJob implements SafeJob
{
    public function __construct(
        private string $chargeId
    ) {}

    public function key(): IdempotencyKey
    {
        return new IdempotencyKey('charge:' . $this->chargeId);
    }

    public function perform(Context $ctx): Outcome
    {
        // Retry-safe work only:
        // - idempotent DB writes
        // - upserts
        // - unique constraints
        return Outcome::ranSuccess();
    }

    public function onSucceededOnce(Context $ctx): void
    {
        // Exactly-once side effects:
        // - emit domain event
        // - send notification
        // - publish message
    }
}
```

Rules:

* `perform()` **must be safe to run multiple times**
* `onSucceededOnce()` **must assume it runs only once globally**

---

## Laravel Job Integration

Your Laravel Job becomes a thin adapter.

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Tetthys\SafeJob\Integration\Laravel\Concerns\HandlesSafeJob;
use Tetthys\SafeJob\Core\Contracts\SafeJob as SafeJobContract;

final class ChargeWalletJob implements ShouldQueue
{
    use HandlesSafeJob;

    public function __construct(
        public string $chargeId
    ) {}

    protected function safeJob(): SafeJobContract
    {
        return new ChargeWalletSafeJob($this->chargeId);
    }
}
```

That is all.

* No manual locking
* No duplicate guards
* No custom retry logic

---

## Failure Handling (Optional)

By default, **no failure is finalized**.
All exceptions bubble up and Laravel retries normally.

You may define a `FailurePolicy` to explicitly finalize certain errors:

```php
protected function failurePolicy(): FailurePolicy
{
    return new class implements FailurePolicy {
        public function finalFailureFor(\Throwable $e): ?FinalFailure
        {
            if ($e instanceof BusinessRuleViolation) {
                return new FinalFailure(
                    code: 'rule_violation',
                    message: $e->getMessage()
                );
            }
            return null;
        }
    };
}
```

Only when `FinalFailure` is returned will the job be permanently marked as **FAILED**.

---

## Guarantees

SafeJob provides:

* ✅ Global **exactly-once success**
* ✅ Safe retries under crashes
* ✅ Duplicate dispatch protection
* ✅ Lease-based concurrency control
* ✅ Deterministic final state (SUCCEEDED / FAILED)

What it does **not** do:

* ❌ Distributed transactions
* ❌ Automatic idempotency inside your domain logic

---

## When to Use

SafeJob is ideal for:

* Payments / Wallets / Escrow
* External API side effects
* Event publishing
* Financial or legal workflows
* Any job where “running twice” is unacceptable

---

## Design Philosophy

> **Jobs must be retryable.
> Side effects must be final.
> The boundary must be explicit.**

SafeJob enforces this boundary by design.

---

## License

MIT
