<?php

declare(strict_types=1);

namespace App\Modules\Communication\Listeners;

use App\Modules\Booking\Contracts\BookingSummaries;
use App\Modules\Booking\Contracts\BookingSummary;
use App\Modules\Booking\Contracts\WaitlistOfferSummary;
use App\Modules\Booking\Events\BookingCancelled;
use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Events\BookingReminderDue;
use App\Modules\Booking\Events\BookingRescheduled;
use App\Modules\Booking\Events\WaitlistOffered;
use App\Modules\Booking\Events\WaitlistPromoted;
use App\Modules\Communication\Enums\NotificationType;
use App\Modules\Communication\Notifications\CustomerNotification;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Contracts\MembershipSummaries;
use App\Modules\Membership\Contracts\MembershipSummary;
use App\Modules\Membership\Events\LowCreditBalance;
use App\Modules\Membership\Events\MembershipExpiringSoon;
use App\Shared\BusinessTime;
use App\View\Format;
use Carbon\CarbonImmutable;

/**
 * Turns domain events of other modules into customer notifications (spec 8.1). Reads them
 * only through the modules' public contracts.
 */
final readonly class SendCustomerNotifications
{
    public function __construct(
        private BookingSummaries $bookings,
        private MembershipSummaries $memberships,
        private BusinessTime $time,
    ) {}

    public function bookingConfirmed(BookingConfirmed $event): void
    {
        $this->aboutBooking($event->bookingId, NotificationType::BookingConfirmed, withCalendar: true);
    }

    public function bookingCancelled(BookingCancelled $event): void
    {
        $this->aboutBooking($event->bookingId, $event->bySession ? NotificationType::SessionCancelled : NotificationType::BookingCancelled);
    }

    public function bookingRescheduled(BookingRescheduled $event): void
    {
        $this->aboutBooking($event->toBookingId, NotificationType::BookingRescheduled, withCalendar: true);
    }

    public function reminder(BookingReminderDue $event): void
    {
        $this->aboutBooking($event->bookingId, NotificationType::BookingReminder);
    }

    public function waitlistPromoted(WaitlistPromoted $event): void
    {
        $this->aboutBooking($event->bookingId, NotificationType::WaitlistPromoted, withCalendar: true);
    }

    public function waitlistOffered(WaitlistOffered $event): void
    {
        $offer = $this->bookings->waitlistOffer($event->entryId);
        $user = $offer instanceof WaitlistOfferSummary ? $this->recipient($offer->userId) : null;

        if ($offer instanceof WaitlistOfferSummary && $user instanceof User) {
            $user->notify(new CustomerNotification(NotificationType::WaitlistOffered, [
                ...$this->when($offer->startsAt, $user),
                'service' => $offer->serviceName,
                'until' => $this->time->toLocal($offer->offeredUntil)->format('H:i'),
            ], route('app.bookings')));
        }
    }

    public function membershipExpiring(MembershipExpiringSoon $event): void
    {
        $this->aboutMembership($event->membershipId, NotificationType::MembershipExpiring);
    }

    public function lowBalance(LowCreditBalance $event): void
    {
        $this->aboutMembership($event->membershipId, NotificationType::LowBalance);
    }

    private function aboutBooking(int $bookingId, NotificationType $type, bool $withCalendar = false): void
    {
        $booking = $this->bookings->find($bookingId);
        $user = $booking instanceof BookingSummary ? $this->recipient($booking->userId) : null;

        if (! $booking instanceof BookingSummary || ! $user instanceof User) {
            return;
        }

        $user->notify(new CustomerNotification(
            $type,
            $this->bookingPlaceholders($booking, $user),
            route('app.bookings.show', $booking->publicId),
            $withCalendar ? $booking->ics : null,
        ));
    }

    private function aboutMembership(int $membershipId, NotificationType $type): void
    {
        $owner = $this->memberships->ownerOf($membershipId);
        $user = $owner === null ? null : $this->recipient($owner);
        $membership = $user instanceof User ? $this->memberships->find($membershipId, $user->locale->value) : null;

        if (! $user instanceof User || ! $membership instanceof MembershipSummary) {
            return;
        }

        $user->notify(new CustomerNotification($type, [
            'package' => $membership->packageName,
            'ends_on' => $membership->endsOn->format('d/m/Y'),
            'balance' => Format::credits($membership->balance, $user->locale->value),
        ], route('app.membership')));
    }

    /**
     * @return array<string, string>
     */
    private function bookingPlaceholders(BookingSummary $booking, User $user): array
    {
        return [
            ...$this->when($booking->startsAt, $user),
            'service' => $booking->serviceName,
            'instructor' => $booking->instructorName,
            'reference' => $booking->reference,
            'reason' => (string) $booking->cancelReason,
        ];
    }

    /**
     * @return array{date: string, time: string}
     */
    private function when(CarbonImmutable $startsAt, User $user): array
    {
        /** @var CarbonImmutable $local */
        $local = $this->time->toLocal($startsAt)->locale($user->locale->value);

        return ['date' => $local->translatedFormat('l d/m/Y'), 'time' => $local->format('H:i')];
    }

    private function recipient(int $userId): ?User
    {
        $user = User::query()->find($userId);

        return $user === null || $user->isAnonymized() ? null : $user;
    }
}
