<?php

declare(strict_types=1);

namespace App\Modules\Communication\Infrastructure;

use App\Modules\Communication\Contracts\SmsSender;
use App\Modules\Communication\Notifications\SmsMessage;
use App\Modules\Identity\Models\User;

/**
 * Laravel notification channel that delivers through the SmsSender adapter.
 */
final readonly class SmsChannel
{
    public function __construct(private SmsSender $sender) {}

    public function send(User $notifiable, SmsMessage $notification): void
    {
        if ($notifiable->phone !== null) {
            $this->sender->send($notifiable->phone, $notification->toSms($notifiable));
        }
    }
}
