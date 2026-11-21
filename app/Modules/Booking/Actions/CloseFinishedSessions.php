<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * MarkNoShows (spec 8): bookings still confirmed after their session ended are closed.
 * Phase 1 has no check-in at the door, so they become attended; with checkin_required on
 * they become no_show. Idempotent: closed bookings are not touched again.
 */
final readonly class CloseFinishedSessions
{
    public function __construct(
        private MarkAttendance $markAttendance,
        private BookingPolicySettings $settings,
        private Clock $clock,
    ) {}

    public function handle(): int
    {
        $outcome = $this->settings->checkin_required ? BookingStatus::NoShow : BookingStatus::Attended;

        $ids = Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereHas('session', fn ($q) => $q->where('ends_at', '<=', $this->clock->now()))
            ->pluck('id');

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $outcome): void {
                $booking = Booking::query()->whereKey($id)->lockForUpdate()->first();

                if ($booking?->status === BookingStatus::Confirmed) {
                    $this->markAttendance->apply($booking, $outcome);
                }
            });
        }

        return $ids->count();
    }
}
