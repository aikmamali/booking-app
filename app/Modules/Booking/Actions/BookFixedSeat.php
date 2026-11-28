<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\FixedSeatStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\FixedSeat;
use App\Modules\Scheduling\Enums\SessionStatus;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\BusinessTime;
use App\Shared\Clock;

/**
 * FS-03: books an active seat into every upcoming class of its weekly schedule within the
 * season. Idempotent: classes the customer already holds a place in are skipped, and a
 * class the seat was cancelled from is not booked again.
 */
final readonly class BookFixedSeat
{
    public function __construct(
        private BookFixedSeatSession $bookOne,
        private BusinessTime $time,
        private Clock $clock,
    ) {}

    /**
     * @return int bookings made (confirmed or awaiting credits)
     */
    public function handle(FixedSeat $seat): int
    {
        if ($seat->status !== FixedSeatStatus::Active) {
            return 0;
        }

        $made = 0;

        foreach ($this->openSessions($seat) as $sessionId) {
            $made += $this->bookOne->handle($seat, $sessionId) instanceof Booking ? 1 : 0;
        }

        return $made;
    }

    /**
     * @return list<int>
     */
    private function openSessions(FixedSeat $seat): array
    {
        $seat->loadMissing('season');
        $handled = Booking::query()->where('fixed_seat_id', $seat->id)->pluck('class_session_id');

        $ids = ClassSession::query()
            ->where('schedule_id', $seat->schedule_id)
            ->where('status', SessionStatus::Scheduled)
            ->where('starts_at', '>', $this->clock->now())
            ->where('starts_at', '>=', $this->time->toUtc($seat->season->starts_on->toDateString(), '00:00'))
            ->where('starts_at', '<', $this->time->toUtc($seat->season->ends_on->toDateString(), '00:00')->addDay())
            ->whereNotIn('id', $handled)
            ->orderBy('starts_at')
            ->pluck('id');

        return array_values(array_map(intval(...), $ids->all()));
    }
}
