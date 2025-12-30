<?php

declare(strict_types=1);

namespace Tetthys\SafeJob\Core\Value;

enum State: string
{
    case NEW = 'new';
    case RUNNING = 'running';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
}
