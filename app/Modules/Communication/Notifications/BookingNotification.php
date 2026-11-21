<?php

declare(strict_types=1);

namespace App\Modules\Communication\Notifications;

use App\Modules\Booking\Contracts\BookingSummary;
use App\Modules\Identity\Models\User;
use App\Shared\BusinessTime;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for the booking emails of spec 8.1. Always queued, so an SMTP failure never
 * affects the booking itself. Texts live in lang/{el,en}/communication.php.
 */
abstract class BookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BookingSummary $booking) {}

    abstract protected function key(): string;

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $when = app(BusinessTime::class)->toLocal($this->booking->startsAt);
        $replace = [
            'name' => $notifiable->first_name,
            'service' => $this->booking->serviceName,
            'date' => $this->localized($when, $notifiable->locale->value)->translatedFormat('l d/m/Y'),
            'time' => $when->format('H:i'),
            'instructor' => $this->booking->instructorName,
            'reference' => $this->booking->reference,
            'reason' => (string) $this->booking->cancelReason,
        ];

        return $this->extend((new MailMessage)
            ->subject(__("communication.{$this->key()}.subject", $replace))
            ->greeting(__('communication.greeting', $replace))
            ->line(__("communication.{$this->key()}.body", $replace))
            ->line(__('communication.details', $replace))
            ->action(__('communication.view_booking'), route('app.bookings.show', $this->booking->publicId)));
    }

    private function localized(CarbonImmutable $moment, string $locale): CarbonImmutable
    {
        /** @var CarbonImmutable */
        return $moment->locale($locale);
    }

    protected function extend(MailMessage $message): MailMessage
    {
        return $message;
    }
}
