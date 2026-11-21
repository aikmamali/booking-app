<?php

declare(strict_types=1);

namespace App\Shared;

use Carbon\CarbonImmutable;

/**
 * Source of the current time. Business logic asks the Clock (or receives the time in a context
 * object) instead of calling now(), so time can be controlled in tests.
 */
interface Clock
{
    /**
     * The current instant in UTC.
     */
    public function now(): CarbonImmutable;
}
