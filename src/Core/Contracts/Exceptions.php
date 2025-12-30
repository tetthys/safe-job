<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Core\Contracts;

final class Exceptions
{
    public const string LEASE_NOT_HELD = 'lease_not_held';
    public const string ALREADY_FINALIZED = 'already_finalized';
    public const string INVARIANT_VIOLATION = 'invariant_violation';
}
