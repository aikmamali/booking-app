<?php

declare(strict_types=1);

use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Communication\Enums\NotificationType;
use App\Modules\Communication\Notifications\CustomerNotification;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Enums\TransactionType;
use App\Modules\Membership\Models\CreditTransaction;
use App\Modules\Scheduling\Actions\ChangeSession;
use App\Modules\Scheduling\Data\SessionChangeData;
use App\Modules\Scheduling\Enums\SessionStatus;
use App\Modules\Scheduling\Exceptions\SessionChangeRejected;
use App\Shared\Credits;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->travelTo('2027-03-20 08:00:00');
    $this->session = bookableSession();
});

it('books a seat, charges the membership and emails a confirmation with an .ics file', function (): void {
    Notification::fake();
    $customer = customerFor($this->session);

    $booking = book($customer, $this->session);

    $debit = CreditTransaction::query()->where('type', TransactionType::Debit)->sole();
    expect($booking->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->source)->toBe(BookingSource::Customer)
        ->and($booking->credits_charged->toDecimal())->toBe('1.00')
        ->and($booking->public_id)->toHaveLength(26)
        ->and($debit->booking_id)->toBe($booking->id)
        ->and(creditsOf($customer))->toBe('9.00');

    assertNotified($customer, NotificationType::BookingConfirmed, check: function (CustomerNotification $n) use ($customer): bool {
        $mail = $n->toMail($customer);

        return str_contains((string) $n->ics, 'DTSTART:20270323T163000Z')
            && $mail->rawAttachments !== []
            && $mail->subject === 'Επιβεβαίωση κράτησης: Group Training, Τρίτη 23/03/2027 18:30';
    });
});

it('charges half credits for a half-credit service', function (): void {
    $this->session->service->update(['credit_cost' => Credits::of('0.5')]);
    $customer = customerFor($this->session);

    book($customer, $this->session);

    expect(creditsOf($customer))->toBe('9.50');
});

it('announces the booking only after commit', function (): void {
    Event::fake([BookingConfirmed::class]);
    $customer = customerFor($this->session);

    DB::beginTransaction();
    book($customer, $this->session);
    Event::assertNotDispatched(BookingConfirmed::class);
    DB::commit();

    Event::assertDispatched(BookingConfirmed::class);
});

it('rejects bookings that break a rule, without charging', function (Closure $arrange, string $reason): void {
    $customer = customerFor($this->session);
    $arrange($this, $customer);

    expect(fn (): Booking => book($customer, $this->session->fresh()))->toThrow(BookingRejected::class, $reason);
    expect(Booking::query()->where('user_id', $customer->id)->active()->count())->toBeLessThanOrEqual(1);
})->with([
    'session cancelled' => [fn ($t) => $t->session->update(['status' => SessionStatus::Cancelled]), 'booking.session_closed'],
    'session started' => [fn ($t) => $t->travelTo('2027-03-23 16:30:00'), 'booking.session_closed'],
    'booking window not open' => [function ($t): void {
        $s = app(BookingPolicySettings::class);
        $s->booking_opens_days_ahead = 2;
        $s->save();
    }, 'booking.session_closed'],
    'full' => [fn ($t) => collect(range(1, 5))->each(fn (): Booking => book(customerFor($t->session), $t->session)), 'booking.full'],
    'already booked' => [fn ($t, $c): Booking => book($c, $t->session), 'booking.duplicate'],
    'daily limit' => [fn ($t, $c): Booking => book($c, bookableSession(['starts_at' => '2027-03-23 06:00:00', 'ends_at' => '2027-03-23 07:00:00'], $t->session->service)), 'booking.daily_limit'],
    'active limit' => [function ($t, $c): void {
        $s = app(BookingPolicySettings::class);
        $s->max_active_bookings = 1;
        $s->save();
        book($c, bookableSession(['starts_at' => '2027-03-24 16:30:00', 'ends_at' => '2027-03-24 17:30:00'], $t->session->service));
    }, 'booking.active_limit'],
]);

it('rejects customers without a usable membership', function (): void {
    $none = User::factory()->customer()->create();
    $poor = customerFor($this->session, '0.5');

    expect(fn (): Booking => book($none, $this->session))->toThrow(BookingRejected::class, 'booking.no_membership')
        ->and(fn (): Booking => book($poor, $this->session))->toThrow(BookingRejected::class, 'booking.insufficient_credits');
});

it('only books customers', function (): void {
    book(User::factory()->instructor()->create(), $this->session);
})->throws(BookingRejected::class, 'booking.not_a_customer');

it('lets staff override limits with a logged reason, but never capacity', function (): void {
    $admin = User::factory()->admin()->create();
    $customer = customerFor($this->session);
    book($customer, bookableSession(['starts_at' => '2027-03-23 06:00:00', 'ends_at' => '2027-03-23 07:00:00'], $this->session->service));

    $booking = book($customer, $this->session, ['source' => BookingSource::Staff, 'actor_id' => $admin->id, 'override_reason' => 'Ειδική περίπτωση']);

    $log = Activity::query()->forSubject($booking)->where('description', 'booked_with_override')->sole();
    expect($log->causer_id)->toBe($admin->id)
        ->and($log->properties['override_reason'])->toBe('Ειδική περίπτωση');

    $full = bookableSession(['capacity' => 0, 'starts_at' => '2027-03-24 16:30:00', 'ends_at' => '2027-03-24 17:30:00'], $this->session->service);
    expect(fn (): Booking => book($customer, $full, ['actor_id' => $admin->id, 'override_reason' => 'x']))->toThrow(BookingRejected::class, 'booking.full');
});

it('refuses an override from someone without the permission', function (): void {
    $customer = customerFor($this->session);

    book($customer, $this->session, ['actor_id' => $customer->id, 'override_reason' => 'trust me']);
})->throws(BookingRejected::class, 'booking.override_not_allowed');

it('has a unique index as a second line of defence against double booking', function (): void {
    $customer = customerFor($this->session);
    $booking = book($customer, $this->session);

    Booking::query()->insert([
        'public_id' => (string) Str::ulid(), 'user_id' => $customer->id, 'class_session_id' => $this->session->id,
        'status' => 'confirmed', 'credits_charged' => '1.00', 'source' => 'customer', 'created_at' => now(), 'updated_at' => now(),
    ]);
})->throws(QueryException::class);

it('lets a customer book again after cancelling', function (): void {
    $customer = customerFor($this->session);
    book($customer, $this->session)->forceFill(['status' => BookingStatus::Cancelled])->save();

    expect(book($customer, $this->session)->status)->toBe(BookingStatus::Confirmed);
});

it('stops staff shrinking a session below its bookings', function (): void {
    book(customerFor($this->session), $this->session);
    book(customerFor($this->session), $this->session);

    app(ChangeSession::class)->handle($this->session->fresh(), new SessionChangeData(
        instructor_id: $this->session->instructor_id, room_id: null, capacity: 1,
        date: '2027-03-23', start_time: '18:30', lock_version: 0,
    ));
})->throws(SessionChangeRejected::class, 'scheduling.errors.capacity_below_taken');
