<?php

declare(strict_types=1);

namespace App\Modules\Communication\Notifications;

final class BookingReminderNotification extends BookingNotification
{
    protected function key(): string
    {
        return 'reminder';
    }
}
