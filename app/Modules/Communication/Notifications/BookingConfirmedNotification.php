<?php

declare(strict_types=1);

namespace App\Modules\Communication\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

final class BookingConfirmedNotification extends BookingNotification
{
    protected function key(): string
    {
        return 'confirmed';
    }

    protected function extend(MailMessage $message): MailMessage
    {
        return $message->attachData($this->booking->ics, "booking-{$this->booking->reference}.ics", ['mime' => 'text/calendar']);
    }
}
