<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Core\Contracts;

use Tetthys\SafeJob\Core\Value\AttemptId;

interface Context
{
    public function attemptId(): AttemptId;

    public function store(): Store;

    public function failurePolicy(): FailurePolicy;

    public function clock(): Clock;

    public function logger(): Logger;
}
