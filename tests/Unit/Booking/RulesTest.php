<?php

declare(strict_types=1);

use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Rules\BookingContext;
use App\Modules\Booking\Rules\BookingRuleChain;
use App\Modules\Booking\Rules\HasCapacity;
use App\Modules\Booking\Rules\HasValidMembership;
use App\Modules\Booking\Rules\NotAlreadyBooked;
use App\Modules\Booking\Rules\RequiredFormsCompleted;
use App\Modules\Booking\Rules\RuleResult;
use App\Modules\Booking\Rules\SessionIsBookable;
use App\Modules\Booking\Rules\WithinActiveLimit;
use App\Modules\Booking\Rules\WithinDailyLimit;
use App\Modules\Booking\Support\EffectivePolicy;
use App\Modules\Membership\Contracts\MembershipChoice;
use App\Modules\Scheduling\Enums\SessionStatus;
use App\Modules\Scheduling\Models\ClassSession;
use Carbon\CarbonImmutable;

/**
 * A context where every rule passes; each test changes one fact.
 */
function ruleContext(array $facts = [], array $session = [], array $policy = []): BookingContext
{
    $model = new ClassSession;
    $model->forceFill([
        'starts_at' => CarbonImmutable::parse('2027-03-23 16:30:00'),
        'capacity' => 5,
        'status' => SessionStatus::Scheduled,
        ...$session,
    ]);

    return new BookingContext(...[
        'userId' => 1,
        'session' => $model,
        'now' => CarbonImmutable::parse('2027-03-20 08:00:00'),
        'policy' => new EffectivePolicy(...[
            'cancellationCutoffMinutes' => 180, 'lateCancelAllowed' => false, 'opensDaysAhead' => 14,
            'closesMinutesBefore' => 0, 'maxBookingsPerDay' => 1, 'maxActiveBookings' => null, ...$policy,
        ]),
        'membership' => MembershipChoice::found(9),
        'takenSeats' => 4,
        'alreadyBooked' => false,
        'bookingsThatDay' => 0,
        'activeBookings' => 0,
        'formsCompleted' => true,
        ...$facts,
    ]);
}

function outcome(RuleResult $r): string
{
    return $r->passed ? 'pass' : $r->reason.($r->overridable ? ' (overridable)' : '');
}

describe('SessionIsBookable', function (): void {
    it('checks state, start and booking window', function (array $facts, array $session, array $policy, string $expected): void {
        expect(outcome((new SessionIsBookable)->check(ruleContext($facts, $session, $policy))))->toBe($expected);
    })->with([
        'open' => [[], [], [], 'pass'],
        'cancelled' => [[], ['status' => SessionStatus::Cancelled], [], 'booking.session_closed'],
        'started' => [['now' => CarbonImmutable::parse('2027-03-23 16:30:00')], [], [], 'booking.session_closed'],
        'window not open yet' => [[], [], ['opensDaysAhead' => 2], 'booking.session_closed (overridable)'],
        'window opened a day ago' => [[], [], ['opensDaysAhead' => 4], 'pass'],
        'closes before start' => [['now' => CarbonImmutable::parse('2027-03-23 16:00:00')], [], ['closesMinutesBefore' => 30], 'booking.session_closed (overridable)'],
        'one minute before it closes' => [['now' => CarbonImmutable::parse('2027-03-23 15:59:00')], [], ['closesMinutesBefore' => 30], 'pass'],
    ]);
});

it('HasCapacity: needs a free seat', function (int $taken, string $expected): void {
    expect(outcome((new HasCapacity)->check(ruleContext(['takenSeats' => $taken]))))->toBe($expected);
})->with([[4, 'pass'], [5, 'booking.full'], [6, 'booking.full']]);

it('NotAlreadyBooked', function (bool $booked, string $expected): void {
    expect(outcome((new NotAlreadyBooked)->check(ruleContext(['alreadyBooked' => $booked]))))->toBe($expected);
})->with([[false, 'pass'], [true, 'booking.duplicate']]);

it('WithinDailyLimit', function (int $that, int $max, string $expected): void {
    expect(outcome((new WithinDailyLimit)->check(ruleContext(['bookingsThatDay' => $that], policy: ['maxBookingsPerDay' => $max]))))->toBe($expected);
})->with([[0, 1, 'pass'], [1, 1, 'booking.daily_limit (overridable)'], [1, 2, 'pass']]);

it('WithinActiveLimit', function (int $active, ?int $max, string $expected): void {
    expect(outcome((new WithinActiveLimit)->check(ruleContext(['activeBookings' => $active], policy: ['maxActiveBookings' => $max]))))->toBe($expected);
})->with([[50, null, 'pass'], [2, 3, 'pass'], [3, 3, 'booking.active_limit (overridable)']]);

it('HasValidMembership', function (MembershipChoice $choice, string $expected): void {
    expect(outcome((new HasValidMembership)->check(ruleContext(['membership' => $choice]))))->toBe($expected);
})->with([
    [MembershipChoice::found(1), 'pass'],
    [MembershipChoice::noMembership(), 'booking.no_membership'],
    [MembershipChoice::insufficientCredits(), 'booking.insufficient_credits'],
]);

it('RequiredFormsCompleted', function (bool $done, string $expected): void {
    expect(outcome((new RequiredFormsCompleted)->check(ruleContext(['formsCompleted' => $done]))))->toBe($expected);
})->with([[true, 'pass'], [false, 'booking.forms_missing (overridable)']]);

describe('BookingRuleChain', function (): void {
    $chain = fn (): BookingRuleChain => new BookingRuleChain([new SessionIsBookable, new HasCapacity, new WithinDailyLimit]);

    it('passes when every rule passes', function () use ($chain): void {
        expect($chain()->firstFailure(ruleContext()))->toBeNull();
    });

    it('stops at the first failure', function () use ($chain): void {
        expect($chain()->firstFailure(ruleContext(['takenSeats' => 5, 'bookingsThatDay' => 1]))?->reason)->toBe('booking.full');
    });

    it('skips only overridable failures when overriding', function () use ($chain): void {
        expect($chain()->firstFailure(ruleContext(['bookingsThatDay' => 1]), override: true))->toBeNull()
            ->and($chain()->firstFailure(ruleContext(['bookingsThatDay' => 1, 'takenSeats' => 5]), override: true)?->reason)->toBe('booking.full');
    });

    it('throws the translated rejection', function () use ($chain): void {
        $chain()->assertPasses(ruleContext(['takenSeats' => 5]));
    })->throws(BookingRejected::class, 'booking.full');
});
