<?php

declare(strict_types=1);

namespace App\Modules\Communication;

use App\Modules\Booking\Events\BookingCancelled;
use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Events\BookingReminderDue;
use App\Modules\Booking\Events\BookingRescheduled;
use App\Modules\Booking\Events\WaitlistOffered;
use App\Modules\Booking\Events\WaitlistPromoted;
use App\Modules\Communication\Contracts\SmsSender;
use App\Modules\Communication\Infrastructure\LogSmsSender;
use App\Modules\Communication\Listeners\SendCustomerNotifications as Send;
use App\Modules\Communication\Models\Announcement;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\Note;
use App\Modules\Communication\Policies\AnnouncementPolicy;
use App\Modules\Communication\Policies\EmailTemplatePolicy;
use App\Modules\Communication\Policies\NotePolicy;
use App\Modules\Membership\Events\LowCreditBalance;
use App\Modules\Membership\Events\MembershipExpiringSoon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class CommunicationServiceProvider extends ServiceProvider
{
    /**
     * Domain event → handler on SendCustomerNotifications (spec 8.1).
     *
     * @var array<class-string, string>
     */
    private const array NOTIFICATIONS = [
        BookingConfirmed::class => 'bookingConfirmed',
        BookingCancelled::class => 'bookingCancelled',
        BookingRescheduled::class => 'bookingRescheduled',
        BookingReminderDue::class => 'reminder',
        WaitlistPromoted::class => 'waitlistPromoted',
        WaitlistOffered::class => 'waitlistOffered',
        MembershipExpiringSoon::class => 'membershipExpiring',
        LowCreditBalance::class => 'lowBalance',
    ];

    public function register(): void
    {
        // Replace with a real gateway adapter once an SMS provider is chosen (spec 12).
        $this->app->bindIf(SmsSender::class, LogSmsSender::class);
    }

    public function boot(): void
    {
        foreach (self::NOTIFICATIONS as $event => $method) {
            Event::listen($event, [Send::class, $method]);
        }

        Gate::policy(Announcement::class, AnnouncementPolicy::class);
        Gate::policy(EmailTemplate::class, EmailTemplatePolicy::class);
        Gate::policy(Note::class, NotePolicy::class);
    }
}
