<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\BookingReminderDue;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Shared\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * BK-09: one reminder per threshold in reminder_hours_before (default 24 h and 2 h).
 * reminded_at records the last one sent, so a reminder is never sent twice and a booking
 * made 3 hours before gets only the 2-hour reminder.
 */
final readonly class SendBookingReminders
{
    public function __construct(
        private BookingPolicySettings $settings,
        private Clock $clock,
    ) {}

    public function handle(): int
    {
        $now = $this->clock->now();
        $longest = max($this->settings->reminder_hours_before ?: [0]);

        $bookings = Booking::query()
            ->with('session')
            ->where('status', BookingStatus::Confirmed)
            ->where('remind', true)
            ->whereHas('session', fn ($q) => $q->where('starts_at', '>', $now)->where('starts_at', '<=', $now->addHours($longest)))
            ->get();

        return DB::transaction(fn (): int => $bookings->filter(fn (Booking $b): bool => $this->remindIfDue($b, $now))->count());
    }

    private function remindIfDue(Booking $booking, CarbonImmutable $now): bool
    {
        $due = $this->latestDueThreshold($booking->session->starts_at, $now);

        if (! $due instanceof CarbonImmutable || ($booking->reminded_at !== null && $booking->reminded_at->gte($due))) {
            return false;
        }

        // A booking made after a threshold passed skips that reminder (no reminder right
        // after booking); it still gets the later ones.
        if ($booking->created_at->gte($due)) {
            return false;
        }

        $booking->forceFill(['reminded_at' => $now])->saveQuietly();
        event(new BookingReminderDue($booking->id));

        return true;
    }

    /**
     * The moment the most recent threshold was crossed, e.g. start − 2 h.
     */
    private function latestDueThreshold(CarbonImmutable $startsAt, CarbonImmutable $now): ?CarbonImmutable
    {
        $crossed = array_filter(
            array_map(fn (int $h): CarbonImmutable => $startsAt->subHours($h), $this->settings->reminder_hours_before),
            fn (CarbonImmutable $moment): bool => $moment->lte($now),
        );

        return $crossed === [] ? null : max($crossed);
    }
}
