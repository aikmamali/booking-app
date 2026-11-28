<?php

declare(strict_types=1);

namespace App\Modules\Communication\Notifications;

use App\Modules\Identity\Models\User;

/**
 * A notification that can be delivered through SmsChannel.
 */
interface SmsMessage
{
    /** Longest text sent as one SMS, including the "..." of a cut text. */
    public const int MAX_LENGTH = 155;

    public function toSms(User $notifiable): string;
}
