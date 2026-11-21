<?php

declare(strict_types=1);

use App\Shared\SystemClock;
use Carbon\CarbonImmutable;

it('returns the current instant in UTC and honours test time', function (): void {
    CarbonImmutable::setTestNow('2026-03-29 00:30:00 UTC');

    $now = (new SystemClock)->now();

    expect($now->toIso8601String())->toBe('2026-03-29T00:30:00+00:00')
        ->and($now->getTimezone()->getName())->toBe('UTC');

    CarbonImmutable::setTestNow();
});
