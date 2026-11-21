<?php

declare(strict_types=1);

namespace App\Shared;

use Carbon\CarbonImmutable;

/**
 * Wall-clock time. Honours Carbon's test time, so travelTo() works in tests.
 */
final class SystemClock implements Clock
{
    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now('UTC');
    }
}
