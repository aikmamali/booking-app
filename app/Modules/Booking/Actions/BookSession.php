<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Data\BookingRequest;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Rules\BookingRuleChain;
use App\Modules\Booking\Support\BookingContextFactory;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Contracts\CreditLedger;
use App\Modules\Scheduling\Models\ClassSession;
use Illuminate\Support\Facades\DB;

/**
 * BK-02 (spec 7.2). The session row is locked (SELECT ... FOR UPDATE), which serialises
 * concurrent bookings for the same class, so the last seat can never be sold twice. The
 * unique index on active bookings is the second line of defence.
 */
final readonly class BookSession
{
    public function __construct(
        private BookingRuleChain $rules,
        private BookingContextFactory $contexts,
        private CreditLedger $ledger,
    ) {}

    public function handle(BookingRequest $request): Booking
    {
        return DB::transaction(function () use ($request): Booking {
            $session = $this->lockSession($request->session_id);

            return $this->bookLocked($request, $session);
        });
    }

    /**
     * Books into a session the caller has already locked, inside the caller's transaction.
     */
    public function bookLocked(BookingRequest $request, ClassSession $session, ?int $ignoreBookingId = null): Booking
    {
        $this->guardCustomer($request->user_id);
        $this->guardOverride($request);

        $context = $this->contexts->make($request->user_id, $session, $ignoreBookingId);
        $this->rules->assertPasses($context, $request->isOverride());

        $cost = $session->service->credit_cost;
        $booking = Booking::query()->create([
            'user_id' => $request->user_id,
            'class_session_id' => $session->id,
            'membership_id' => $context->membership->membershipId,
            'status' => BookingStatus::Confirmed,
            'credits_charged' => $cost,
            'source' => $request->source,
            'remind' => $request->remind,
        ]);

        $this->ledger->debit((int) $context->membership->membershipId, $cost, $booking->id, $request->actor_id);
        $this->audit($booking, $request);

        event(new BookingConfirmed($booking->id));

        return $booking;
    }

    public function lockSession(int $sessionId): ClassSession
    {
        /** @var ClassSession */
        return ClassSession::query()->with('service')->whereKey($sessionId)->lockForUpdate()->firstOrFail();
    }

    private function guardCustomer(int $userId): void
    {
        $user = User::query()->find($userId);

        if ($user === null || $user->isAnonymized() || ! $user->hasRole(Role::Customer->value)) {
            throw BookingRejected::because('booking.not_a_customer');
        }
    }

    private function guardOverride(BookingRequest $request): void
    {
        if (! $request->isOverride()) {
            return;
        }

        $actor = $request->actor_id === null ? null : User::query()->find($request->actor_id);

        if ($actor === null || ! $actor->can('override_booking_rules')) {
            throw BookingRejected::because('booking.override_not_allowed');
        }
    }

    private function audit(Booking $booking, BookingRequest $request): void
    {
        $log = activity('booking')->performedOn($booking)->event('booked')
            ->withProperties(['source' => $request->source->value, 'override_reason' => $request->override_reason]);

        if ($request->actor_id !== null) {
            $log->causedBy(User::query()->find($request->actor_id));
        }

        $log->log($request->isOverride() ? 'booked_with_override' : 'booked');
    }
}
