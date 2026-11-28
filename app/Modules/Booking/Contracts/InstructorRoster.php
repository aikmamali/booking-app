<?php

declare(strict_types=1);

namespace App\Modules\Booking\Contracts;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\BusinessTime;
use Illuminate\Database\Eloquent\Builder;

/**
 * Public read contract: which customers an instructor teaches, and their bookings
 * (RP-03 notes, ADR 0011). A null instructor means "any instructor" (admin).
 */
final readonly class InstructorRoster
{
    public function __construct(private BusinessTime $time) {}

    /**
     * Customers with at least one held booking (confirmed, attended or no-show) in a class
     * of this instructor.
     *
     * @return list<int>
     */
    public function customerIds(int $instructorId): array
    {
        return array_values(array_map(intval(...), $this->held($instructorId)->distinct()->pluck('user_id')->all()));
    }

    public function teaches(int $instructorId, int $userId): bool
    {
        return $this->held($instructorId)->where('user_id', $userId)->exists();
    }

    /**
     * The customer's held bookings, newest first, as "Pilates · 20/03/2027 18:00".
     *
     * @return array<int, string>
     */
    public function bookingChoices(int $userId, ?int $instructorId, int $limit = 30): array
    {
        return $this->labels(array_values($this->held($instructorId)->where('user_id', $userId)
            ->orderByDesc(ClassSession::query()->select('starts_at')->whereColumn('class_sessions.id', 'bookings.class_session_id'))
            ->limit($limit)->pluck('id')->map(fn (mixed $id): int => (int) $id)->values()->all()));
    }

    public function bookingBelongs(int $bookingId, int $userId, ?int $instructorId): bool
    {
        return $this->held($instructorId)->whereKey($bookingId)->where('user_id', $userId)->exists();
    }

    /**
     * Who is booked into a class: active and closed bookings, not cancelled ones.
     *
     * @return list<RosterEntry>
     */
    public function attendees(int $sessionId): array
    {
        return array_values(Booking::query()->with('user')
            ->where('class_session_id', $sessionId)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Attended, BookingStatus::NoShow])
            ->get()
            ->sortBy(fn (Booking $b): string => $b->user->last_name.' '.$b->user->first_name)
            ->map(fn (Booking $b): RosterEntry => new RosterEntry($b->id, $b->user_id, $b->user->fullName(), $b->status->label()))
            ->all());
    }

    /**
     * @param  list<int>  $bookingIds
     * @return array<int, string>
     */
    public function labels(array $bookingIds): array
    {
        if ($bookingIds === []) {
            return [];
        }

        $found = [];

        foreach (Booking::query()->with('session.service')->whereKey($bookingIds)->get() as $booking) {
            $found[$booking->id] = $booking->session->service->name.' · '
                .$this->time->toLocal($booking->session->starts_at)->format('d/m/Y H:i');
        }

        // Keep the caller's order.
        $labels = [];

        foreach ($bookingIds as $id) {
            if (isset($found[$id])) {
                $labels[$id] = $found[$id];
            }
        }

        return $labels;
    }

    /**
     * @return Builder<Booking>
     */
    private function held(?int $instructorId): Builder
    {
        return Booking::query()
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Attended, BookingStatus::NoShow])
            ->when($instructorId !== null, fn (Builder $q) => $q->whereHas('session', fn (Builder $s) => $s->where('instructor_id', $instructorId)));
    }
}
