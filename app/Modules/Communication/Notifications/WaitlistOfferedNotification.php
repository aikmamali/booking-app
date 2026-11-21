<?php

declare(strict_types=1);

namespace App\Modules\Communication\Notifications;

use App\Modules\Booking\Contracts\WaitlistOfferSummary;
use App\Modules\Identity\Models\User;
use App\Shared\BusinessTime;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "A seat is free, accept by …" (offer strategy).
 */
final class WaitlistOfferedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WaitlistOfferSummary $offer) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $time = app(BusinessTime::class);
        /** @var CarbonImmutable $when */
        $when = $time->toLocal($this->offer->startsAt)->locale($notifiable->locale->value);
        $replace = [
            'name' => $notifiable->first_name,
            'service' => $this->offer->serviceName,
            'date' => $when->translatedFormat('l d/m/Y'),
            'time' => $when->format('H:i'),
            'until' => $time->toLocal($this->offer->offeredUntil)->format('H:i'),
        ];

        return (new MailMessage)
            ->subject(__('communication.waitlist_offered.subject', $replace))
            ->greeting(__('communication.greeting', $replace))
            ->line(__('communication.waitlist_offered.body', $replace))
            ->action(__('communication.waitlist_offered.action'), route('app.bookings'));
    }
}
