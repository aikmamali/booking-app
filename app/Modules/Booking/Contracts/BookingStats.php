<?php

declare(strict_types=1);

namespace App\Modules\Booking\Contracts;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\WaitlistEntry;
use App\Modules\Scheduling\Models\ClassSession;
use Carbon\CarbonImmutable;

/**
 * Public read contract of Booking for the Reporting dashboard.
 */
final class BookingStats
{
    /**
     * Sessions starting in [from, to) with seats taken (bookings that held or used a seat).
     *
     * @return list<SessionOccupancyRow>
     */
    public function sessions(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $sessions = ClassSession::query()->with(['service', 'instructor'])
            ->where('starts_at', '>=', $from)->where('starts_at', '<', $to)->orderBy('starts_at')->get();
        $ids = $sessions->modelKeys();

        $booked = Booking::query()->whereIn('class_session_id', $ids)
            ->whereIn('status', [...BookingStatus::active(), BookingStatus::Attended, BookingStatus::NoShow])
            ->selectRaw('class_session_id, count(*) as n')->groupBy('class_session_id')->toBase()->pluck('n', 'class_session_id');
        $waiting = WaitlistEntry::query()->live()->whereIn('class_session_id', $ids)
            ->selectRaw('class_session_id, count(*) as n')->groupBy('class_session_id')->toBase()->pluck('n', 'class_session_id');

        return array_values($sessions->map(fn (ClassSession $s): SessionOccupancyRow => new SessionOccupancyRow(
            startsAt: $s->starts_at,
            serviceName: $s->service->name,
            instructorName: $s->instructor->fullName(),
            booked: (int) ($booked[$s->id] ?? 0),
            capacity: $s->capacity,
            waiting: (int) ($waiting[$s->id] ?? 0),
            cancelled: $s->isCancelled(),
        ))->all());
    }

    /**
     * Share of closed bookings in [from, to) that were no-shows, or null when there are none.
     */
    public function noShowRate(CarbonImmutable $from, CarbonImmutable $to): ?float
    {
        $counts = Booking::query()
            ->whereIn('status', [BookingStatus::Attended, BookingStatus::NoShow])
            ->whereHas('session', fn ($q) => $q->where('starts_at', '>=', $from)->where('starts_at', '<', $to))
            ->selectRaw('status, count(*) as n')->groupBy('status')->toBase()->pluck('n', 'status');

        $total = (int) $counts->sum();

        return $total === 0 ? null : (int) ($counts[BookingStatus::NoShow->value] ?? 0) / $total;
    }
}
