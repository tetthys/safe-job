<?php

declare(strict_types=1);

return [
    'table' => 'safe_jobs',

    // default lease TTL
    'lease_ttl_seconds' => 30,

    // when RUNNING and expired, allow stealing lease
    'allow_steal_expired_lease' => true,
];
