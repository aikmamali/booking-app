<?php

declare(strict_types=1);

namespace App\Modules\Communication\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * "You got a seat": the waitlist booked the customer (auto_book) or they accepted an offer.
 */
final class WaitlistPromotedNotification extends BookingNotification
{
    protected function key(): string
    {
        return 'waitlist_promoted';
    }

    protected function extend(MailMessage $message): MailMessage
    {
        return $message->attachData($this->booking->ics, "booking-{$this->booking->reference}.ics", ['mime' => 'text/calendar']);
    }
}
