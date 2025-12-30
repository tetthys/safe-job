<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Integration\Laravel\Support;

use Tetthys\SafeJob\Core\Contracts\Logger;

final readonly class LaravelLogger implements Logger
{
    public function __construct(private \Psr\Log\LoggerInterface $logger) {}

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }
}
