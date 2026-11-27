<?php

declare(strict_types=1);

namespace App\Providers;

use App\Livewire\ModuleComponents;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Monitoring\AlertOnFailedJob;
use App\Shared\BusinessTime;
use App\Shared\Clock;
use App\Shared\SystemClock;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->bind(BusinessTime::class, fn ($app): BusinessTime => new BusinessTime(
            $app->make(BookingPolicySettings::class)->timezone,
            $app->make(Clock::class),
        ));
    }

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        ModuleComponents::register();

        Event::listen(JobFailed::class, AlertOnFailedJob::class);

        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }
}
