<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Integration\Laravel;

use Illuminate\Support\ServiceProvider;
use Tetthys\SafeJob\Core\Contracts\Clock;
use Tetthys\SafeJob\Core\Contracts\Logger;
use Tetthys\SafeJob\Core\Contracts\Runner;
use Tetthys\SafeJob\Core\Contracts\Store;
use Tetthys\SafeJob\Integration\Laravel\Support\DbRunner;
use Tetthys\SafeJob\Integration\Laravel\Support\DbStore;
use Tetthys\SafeJob\Integration\Laravel\Support\LaravelClock;
use Tetthys\SafeJob\Integration\Laravel\Support\LaravelLogger;

final class LaravelSafeJobServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/tetthys-safejob.php', 'tetthys-safejob');

        $this->app->singleton(Clock::class, LaravelClock::class);
        $this->app->singleton(Logger::class, LaravelLogger::class);

        $this->app->singleton(Store::class, function ($app): Store {
            return new DbStore(
                table: (string) config('tetthys-safejob.table', 'safe_jobs'),
                allowStealExpiredLease: (bool) config('tetthys-safejob.allow_steal_expired_lease', true),
            );
        });

        $this->app->singleton(Runner::class, function ($app): Runner {
            return new DbRunner();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../../config/tetthys-safejob.php' => config_path('tetthys-safejob.php'),
        ], 'tetthys-safejob-config');

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');
        }
    }
}
