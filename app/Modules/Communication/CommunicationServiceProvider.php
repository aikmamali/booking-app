<?php

declare(strict_types=1);

namespace App\Modules\Communication;

use App\Modules\Booking\Events\BookingCancelled;
use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Events\BookingReminderDue;
use App\Modules\Booking\Events\WaitlistOffered;
use App\Modules\Booking\Events\WaitlistPromoted;
use App\Modules\Communication\Listeners\SendBookingEmails;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class CommunicationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(BookingConfirmed::class, [SendBookingEmails::class, 'confirmed']);
        Event::listen(BookingCancelled::class, [SendBookingEmails::class, 'cancelled']);
        Event::listen(BookingReminderDue::class, [SendBookingEmails::class, 'reminder']);
        Event::listen(WaitlistPromoted::class, [SendBookingEmails::class, 'waitlistPromoted']);
        Event::listen(WaitlistOffered::class, [SendBookingEmails::class, 'waitlistOffered']);
    }
}
