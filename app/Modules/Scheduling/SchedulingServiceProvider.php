<?php

declare(strict_types=1);

namespace App\Modules\Scheduling;

use App\Modules\Scheduling\Contracts\NoOccupancy;
use App\Modules\Scheduling\Contracts\SessionOccupancy;
use App\Modules\Scheduling\Models\ClassSession;
use App\Modules\Scheduling\Models\Closure;
use App\Modules\Scheduling\Models\Schedule;
use App\Modules\Scheduling\Policies\ClassSessionPolicy;
use App\Modules\Scheduling\Policies\ClosurePolicy;
use App\Modules\Scheduling\Policies\SchedulePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class SchedulingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Replaced by the Booking module once bookings exist (MVP step 4).
        $this->app->bindIf(SessionOccupancy::class, NoOccupancy::class);
    }

    public function boot(): void
    {
        Gate::policy(Schedule::class, SchedulePolicy::class);
        Gate::policy(Closure::class, ClosurePolicy::class);
        Gate::policy(ClassSession::class, ClassSessionPolicy::class);
    }
}
