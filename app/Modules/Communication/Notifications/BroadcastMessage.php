<?php

declare(strict_types=1);

namespace App\Modules\Communication\Notifications;

use App\Modules\Communication\Enums\Channel;
use App\Modules\Communication\Infrastructure\SmsChannel;
use App\Modules\Identity\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * CM-03: a bulk message. Sent by SendBroadcastJob, which is already queued, so this
 * notification is sent synchronously. Emails end with a way to opt out.
 */
final class BroadcastMessage extends Notification implements SmsMessage
{
    /**
     * @param  list<string>  $channels
     */
    public function __construct(
        public string $subject,
        public string $body,
        public array $channels,
    ) {}

    /**
     * @return list<string|class-string>
     */
    public function via(User $notifiable): array
    {
        $via = [];

        foreach ($this->channels as $channel) {
            if ($channel === Channel::Mail->value) {
                $via[] = 'mail';
            } elseif ($channel === Channel::Sms->value && $notifiable->phone !== null) {
                $via[] = SmsChannel::class;
            }
        }

        return $via;
    }

    public function toMail(User $notifiable): MailMessage
    {
        $locale = $notifiable->locale->value;
        $mail = (new MailMessage)->subject($this->subject)
            ->greeting(__('communication.greeting', ['name' => $notifiable->first_name], $locale));

        foreach (preg_split('/\R{2,}/', $this->body) ?: [] as $paragraph) {
            $mail->line($paragraph);
        }

        return $mail->line(__('communication.broadcasts.unsubscribe', ['url' => route('app.profile')], $locale));
    }

    public function toSms(User $notifiable): string
    {
        return Str::limit($this->subject.': '.preg_replace('/\s+/', ' ', $this->body), SmsMessage::MAX_LENGTH - 3);
    }
}
