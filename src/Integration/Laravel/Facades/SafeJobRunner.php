<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Integration\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Tetthys\SafeJob\Core\Contracts\Runner;

/**
 * @method static \Tetthys\SafeJob\Core\Value\Outcome handle(\Tetthys\SafeJob\Core\Contracts\SafeJob $job, \Tetthys\SafeJob\Core\Contracts\Context $ctx, int $leaseTtlSeconds = 30)
 */
final class SafeJobRunner extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Runner::class;
    }
}
