<?php

declare(strict_types=1);

use App\Modules\Membership\Jobs\ExpireMembershipsJob;
use App\Modules\Membership\Jobs\WarnExpiringMembershipsJob;
use App\Modules\Scheduling\Jobs\GenerateSessionsJob;
use Illuminate\Support\Facades\Schedule;

/*
| Periodic jobs (spec section 8). Run by one cron entry: `* * * * * php artisan schedule:run`.
| Every job is idempotent and must not overlap itself.
*/

Schedule::job(new GenerateSessionsJob)
    ->dailyAt('02:00')
    ->timezone('Europe/Athens')
    ->withoutOverlapping()
    ->name('generate-sessions');

Schedule::job(new ExpireMembershipsJob)
    ->dailyAt('00:10')
    ->timezone('Europe/Athens')
    ->withoutOverlapping()
    ->name('expire-memberships');

Schedule::job(new WarnExpiringMembershipsJob)
    ->dailyAt('10:00')
    ->timezone('Europe/Athens')
    ->withoutOverlapping()
    ->name('warn-expiring-memberships');
