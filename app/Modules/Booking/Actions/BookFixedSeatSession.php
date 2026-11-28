<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Data\BookingRequest;
use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\FixedSeatAwaitingCredits;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\FixedSeat;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Booking\Support\BookingContextFactory;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * FS-03 for one class. With credits the booking is confirmed and charged as usual. Without
 * them the place is held as pending_credits for fixed_seat_pending_credits_hours (never past
 * the start), and the customer is told to buy a package. A full or closed class is skipped.
 */
final readonly class BookFixedSeatSession
{
    private const array CREDIT_FAILURES = ['booking.no_membership', 'booking.insufficient_credits'];

    public function __construct(
        private BookSession $book,
        private BookingContextFactory $contexts,
        private BookingPolicySettings $settings,
        private Clock $clock,
    ) {}

    public function handle(FixedSeat $seat, int $sessionId): ?Booking
    {
        return DB::transaction(function () use ($seat, $sessionId): ?Booking {
            $session = $this->book->lockSession($sessionId);
            $request = new BookingRequest(
                user_id: $seat->user_id,
                session_id: $sessionId,
                source: BookingSource::FixedSeat,
                fixed_seat_id: $seat->id,
            );

            try {
                return $this->book->bookLocked($request, $session);
            } catch (BookingRejected $e) {
                return in_array($e->key, self::CREDIT_FAILURES, true) ? $this->hold($seat, $session) : null;
            }
        });
    }

    private function hold(FixedSeat $seat, ClassSession $session): ?Booking
    {
        $context = $this->contexts->make($seat->user_id, $session);

        if ($context->alreadyBooked || $context->takenSeats >= $session->capacity) {
            return null;
        }

        $until = $this->clock->now()->addHours($this->settings->fixed_seat_pending_credits_hours);

        $booking = Booking::query()->create([
            'user_id' => $seat->user_id,
            'class_session_id' => $session->id,
            'fixed_seat_id' => $seat->id,
            'status' => BookingStatus::PendingCredits,
            'hold_until' => $until->min($session->starts_at),
            'credits_charged' => $session->service->credit_cost,
            'source' => BookingSource::FixedSeat,
            'remind' => true,
        ]);

        event(new FixedSeatAwaitingCredits($booking->id));

        return $booking;
    }
}
