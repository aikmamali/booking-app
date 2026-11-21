<?php

declare(strict_types=1);

namespace App\Modules\Identity\Notifications;

use App\Modules\Identity\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $url = route('app.invitation', ['token' => $this->token, 'email' => $notifiable->email]);

        return (new MailMessage)
            ->subject(__('identity.invitation.mail.subject', ['app' => __('app.name')]))
            ->greeting(__('identity.invitation.mail.greeting', ['name' => $notifiable->first_name]))
            ->line(__('identity.invitation.mail.intro', ['app' => __('app.name')]))
            ->action(__('identity.invitation.mail.action'), $url)
            ->line(__('identity.invitation.mail.expiry', ['days' => 7]));
    }
}
