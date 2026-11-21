<?php

declare(strict_types=1);

namespace App\Modules\Communication\Infrastructure;

use App\Modules\Communication\Contracts\SmsSender;
use Illuminate\Support\Facades\Log;

/**
 * Default adapter until an SMS provider is chosen: writes the message to the log.
 */
final class LogSmsSender implements SmsSender
{
    public function send(string $to, string $message): void
    {
        Log::info('SMS not sent (no provider configured)', ['to' => $to, 'message' => $message]);
    }
}
