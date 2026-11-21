<?php

declare(strict_types=1);

namespace App\Modules\Booking\Contracts;

use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\WaitlistEntry;
use App\Modules\Booking\Support\BookingCalendar;
use Carbon\CarbonImmutable;

/**
 * Public read contract of the Booking module.
 */
final class BookingSummaries
{
    public function find(int $bookingId): ?BookingSummary
    {
        $booking = Booking::query()->with(['session.service', 'session.instructor'])->find($bookingId);

        return $booking === null ? null : $this->summarise($booking);
    }

    public function waitlistOffer(int $entryId): ?WaitlistOfferSummary
    {
        $entry = WaitlistEntry::query()->with('session.service')->find($entryId);

        if ($entry?->offered_until === null) {
            return null;
        }

        return new WaitlistOfferSummary($entry->user_id, $entry->session->service->name, $entry->session->starts_at, $entry->offered_until);
    }

    /**
     * The customer's next active bookings, soonest first.
     *
     * @return list<BookingSummary>
     */
    public function upcomingFor(int $userId, CarbonImmutable $now, int $limit = 3): array
    {
        return array_values(Booking::query()
            ->with(['session.service', 'session.instructor'])
            ->where('user_id', $userId)
            ->active()
            ->whereHas('session', fn ($q) => $q->where('starts_at', '>', $now))
            ->get()
            ->sortBy(fn (Booking $b): int => $b->session->starts_at->getTimestamp())
            ->take($limit)
            ->map(fn (Booking $b): BookingSummary => $this->summarise($b))
            ->all());
    }

    private function summarise(Booking $booking): BookingSummary
    {
        $session = $booking->session;

        return new BookingSummary(
            id: $booking->id,
            publicId: $booking->public_id,
            reference: $booking->reference(),
            userId: $booking->user_id,
            serviceName: $session->service->name,
            instructorName: $session->instructor->fullName(),
            startsAt: $session->starts_at,
            endsAt: $session->ends_at,
            status: $booking->status->value,
            cancelReason: $booking->cancel_reason,
            ics: BookingCalendar::ics($booking),
            googleCalendarUrl: BookingCalendar::googleUrl($booking),
        );
    }
}
