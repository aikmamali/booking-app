<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Models\Booking;
use App\Modules\Membership\Contracts\CreditLedger;
use App\Modules\Membership\Contracts\MembershipSelector;
use App\Shared\BusinessTime;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * FS-03: after the customer buys a package, held fixed-seat bookings are confirmed and
 * charged, soonest class first, while the credits last.
 */
final readonly class ConfirmPendingCreditBookings
{
    public function __construct(
        private MembershipSelector $selector,
        private CreditLedger $ledger,
        private BusinessTime $time,
        private Clock $clock,
    ) {}

    public function handle(int $userId): int
    {
        $ids = Booking::query()->where('user_id', $userId)
            ->where('status', BookingStatus::PendingCredits)
            ->where('hold_until', '>', $this->clock->now())
            ->with('session')->get()
            ->sortBy(fn (Booking $b): int => $b->session->starts_at->getTimestamp())
            ->pluck('id');

        $confirmed = 0;

        foreach ($ids as $id) {
            $confirmed += DB::transaction(fn (): int => $this->confirm((int) $id));
        }

        return $confirmed;
    }

    private function confirm(int $bookingId): int
    {
        $booking = Booking::query()->with('session.service')->whereKey($bookingId)->lockForUpdate()->first();

        if ($booking?->status !== BookingStatus::PendingCredits) {
            return 0;
        }

        $session = $booking->session;
        $choice = $this->selector->choose($booking->user_id, $session->service_id, $session->service->credit_cost,
            $this->time->localDate($session->starts_at)->format('Y-m-d'));

        if (! $choice->isFound()) {
            return 0;
        }

        $booking->forceFill([
            'status' => BookingStatus::Confirmed,
            'membership_id' => $choice->membershipId,
            'credits_charged' => $session->service->credit_cost,
            'hold_until' => null,
        ])->save();
        $this->ledger->debit((int) $choice->membershipId, $session->service->credit_cost, $booking->id);

        event(new BookingConfirmed($booking->id));

        return 1;
    }
}
