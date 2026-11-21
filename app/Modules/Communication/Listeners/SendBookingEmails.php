<?php

declare(strict_types=1);

namespace App\Modules\Communication\Listeners;

use App\Modules\Booking\Contracts\BookingSummaries;
use App\Modules\Booking\Contracts\BookingSummary;
use App\Modules\Booking\Events\BookingCancelled;
use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Events\BookingReminderDue;
use App\Modules\Communication\Notifications\BookingCancelledNotification;
use App\Modules\Communication\Notifications\BookingConfirmedNotification;
use App\Modules\Communication\Notifications\BookingNotification;
use App\Modules\Communication\Notifications\BookingReminderNotification;
use App\Modules\Identity\Models\User;

/**
 * Turns booking events into customer emails (spec 8.1). The rest of the notifications,
 * preferences and editable templates arrive with step 6.
 */
final readonly class SendBookingEmails
{
    public function __construct(private BookingSummaries $bookings) {}

    public function confirmed(BookingConfirmed $event): void
    {
        $this->send($event->bookingId, BookingConfirmedNotification::class);
    }

    public function cancelled(BookingCancelled $event): void
    {
        $this->send($event->bookingId, BookingCancelledNotification::class);
    }

    public function reminder(BookingReminderDue $event): void
    {
        $this->send($event->bookingId, BookingReminderNotification::class);
    }

    /**
     * @param  class-string<BookingNotification>  $notification
     */
    private function send(int $bookingId, string $notification): void
    {
        $summary = $this->bookings->find($bookingId);
        $user = $summary instanceof BookingSummary ? User::query()->find($summary->userId) : null;

        if ($summary instanceof BookingSummary && $user !== null && ! $user->isAnonymized()) {
            $user->notify(new $notification($summary));
        }
    }
}
