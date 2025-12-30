<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Integration\Laravel;

use Illuminate\Support\ServiceProvider;
use Tetthys\SafeJob\Core\Contracts\Clock;
use Tetthys\SafeJob\Core\Contracts\Logger;
use Tetthys\SafeJob\Integration\Laravel\Contracts\LaravelContextFactory;
use Tetthys\SafeJob\Integration\Laravel\Support\{
    LaravelClock,
    LaravelLogger,
    DefaultLaravelContextFactory
};

final class SafeJobServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Clock::class, LaravelClock::class);

        $this->app->singleton(Logger::class, function ($app) {
            return new LaravelLogger($app->make(\Psr\Log\LoggerInterface::class));
        });

        $this->app->singleton(LaravelContextFactory::class, DefaultLaravelContextFactory::class);

        // NOTE:
        // - Store, Runner, FailurePolicy는 앱(사용자)에서 바인딩하도록 두는 것이 "얇은 어댑터" 원칙에 맞습니다.
        // - 필요하면 여기서 기본 구현체(예: RedisStore, DefaultRunner)를 제공할 수도 있으나,
        //   그 경우 Integration이 두꺼워지므로 권장하지 않습니다.
    }
}
