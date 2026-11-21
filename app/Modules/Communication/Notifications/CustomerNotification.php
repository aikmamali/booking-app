<?php

declare(strict_types=1);

namespace App\Modules\Communication\Notifications;

use App\Modules\Communication\Enums\Channel;
use App\Modules\Communication\Enums\NotificationType;
use App\Modules\Communication\Infrastructure\SmsChannel;
use App\Modules\Communication\Support\Preferences;
use App\Modules\Communication\Support\TemplateRenderer;
use App\Modules\Identity\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Every transactional customer notification (spec 8.1). Always queued, so a mail or SMS
 * failure never affects a booking. Channels follow the customer's preferences (CM-02);
 * wording comes from the editable templates (CM-01).
 *
 * Properties are not readonly: the queue must be able to rebuild the object.
 */
final class CustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string>  $replace  placeholder values, without the user's name
     */
    public function __construct(
        public NotificationType $type,
        public array $replace,
        public ?string $actionUrl = null,
        public ?string $ics = null,
    ) {}

    /**
     * @return list<string|class-string>
     */
    public function via(User $notifiable): array
    {
        return array_map(
            fn (Channel $c): string => $c === Channel::Sms ? SmsChannel::class : 'mail',
            app(Preferences::class)->channelsFor($notifiable->id, $this->type),
        );
    }

    public function toMail(User $notifiable): MailMessage
    {
        $text = $this->render($notifiable);
        $mail = (new MailMessage)
            ->subject($text['subject'])
            ->greeting(__('communication.greeting', ['name' => $notifiable->first_name], $notifiable->locale->value));

        foreach (preg_split('/\R{2,}/', trim($text['body'])) ?: [] as $paragraph) {
            $mail->line($paragraph);
        }

        if ($this->actionUrl !== null) {
            $mail->action(__('communication.actions.'.$this->type->value, [], $notifiable->locale->value), $this->actionUrl);
        }

        if ($this->ics !== null) {
            $mail->attachData($this->ics, 'booking.ics', ['mime' => 'text/calendar']);
        }

        return $mail;
    }

    /**
     * SMS carries the subject line only, cut to one message.
     */
    public function toSms(User $notifiable): string
    {
        return Str::limit($this->render($notifiable)['subject'], 155);
    }

    /**
     * @return array{subject: string, body: string}
     */
    public function render(User $notifiable): array
    {
        return app(TemplateRenderer::class)->render(
            $this->type,
            $notifiable->locale->value,
            ['name' => $notifiable->first_name, ...$this->replace],
        );
    }
}
