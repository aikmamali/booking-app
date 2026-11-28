<?php

declare(strict_types=1);

use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Actions\AdjustCredits;
use App\Modules\Membership\Actions\ExpireMemberships;
use App\Modules\Membership\Actions\WarnExpiringMemberships;
use App\Modules\Membership\Contracts\CreditLedger;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Enums\TransactionType;
use App\Modules\Membership\Events\CreditsAdjusted;
use App\Modules\Membership\Events\LowCreditBalance;
use App\Modules\Membership\Events\MembershipExpired;
use App\Modules\Membership\Events\MembershipExpiringSoon;
use App\Modules\Membership\Exceptions\LedgerRejected;
use App\Modules\Membership\Models\CreditTransaction;
use App\Shared\Credits;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;

beforeEach(fn () => $this->travelTo('2027-03-20 08:00:00'));

function balanceOf(int $membershipId): string
{
    return app(CreditLedger::class)->balance($membershipId)->toDecimal();
}

it('corrects credits with a reason and records who did it', function (): void {
    Event::fake([CreditsAdjusted::class]);
    $membership = soldMembership();
    $admin = User::factory()->admin()->create();

    app(AdjustCredits::class)->handle($membership, Credits::of('-2.5'), ' Λάθος χρέωση ', $admin->id);
    app(AdjustCredits::class)->handle($membership, Credits::of('1'), 'Δώρο', $admin->id);

    $entry = CreditTransaction::query()->where('type', TransactionType::Adjustment)->orderBy('id')->firstOrFail();
    expect(balanceOf($membership->id))->toBe('8.50')
        ->and($entry->reason)->toBe('Λάθος χρέωση')
        ->and($entry->actor_id)->toBe($admin->id);
    Event::assertDispatchedTimes(CreditsAdjusted::class, 2);
});

it('refuses corrections without a reason or below zero', function (string $amount, string $reason, string $error): void {
    $membership = soldMembership(package: ['credits' => Credits::of(2)]);

    expect(fn () => app(AdjustCredits::class)->handle($membership, Credits::of($amount), $reason, null))
        ->toThrow(LedgerRejected::class, $error);
    expect(balanceOf($membership->id))->toBe('2.00');
})->with([
    ['1', '', 'membership.errors.reason_required'],
    ['-2.5', 'x', 'membership.errors.insufficient_credits'],
]);

it('expires memberships after their last day and writes off unused credits', function (): void {
    Event::fake([MembershipExpired::class]);
    $membership = soldMembership(package: ['validity_days' => 3, 'credits' => Credits::of('4.5')], startsOn: '2027-03-20'); // ends 22 March

    $this->travelTo('2027-03-22 21:59:00'); // 23:59 Athens on the last day
    expect(app(ExpireMemberships::class)->handle())->toBe(0);

    $this->travelTo('2027-03-22 22:01:00'); // 00:01 Athens the next day
    expect(app(ExpireMemberships::class)->handle())->toBe(1)
        ->and(app(ExpireMemberships::class)->handle())->toBe(0);

    $entry = CreditTransaction::query()->where('type', TransactionType::Expire)->sole();
    expect($membership->fresh()?->status)->toBe(MembershipStatus::Expired)
        ->and($entry->amount->toDecimal())->toBe('-4.50')
        ->and(balanceOf($membership->id))->toBe('0.00');
    Event::assertDispatched(MembershipExpired::class, fn (MembershipExpired $e): bool => $e->forfeitedCredits === '4.50');
});

it('expires an empty membership without a ledger entry', function (): void {
    $membership = soldMembership(package: ['validity_days' => 1, 'credits' => Credits::of(1)]);
    app(AdjustCredits::class)->handle($membership, Credits::of(-1), 'used', null);

    $this->travelTo('2027-03-22 08:00:00');
    app(ExpireMemberships::class)->handle();

    expect($membership->fresh()?->status)->toBe(MembershipStatus::Expired)
        ->and(CreditTransaction::query()->where('type', TransactionType::Expire)->exists())->toBeFalse();
});

it('warns once when a membership is about to expire', function (): void {
    Event::fake([MembershipExpiringSoon::class, LowCreditBalance::class]);
    $soon = soldMembership(package: ['validity_days' => 8]);   // ends 27 March, 7 days away
    $later = soldMembership(package: ['validity_days' => 9]);  // ends 28 March, 8 days away

    $first = app(WarnExpiringMemberships::class)->handle();
    $second = app(WarnExpiringMemberships::class)->handle();

    expect($first['expiring'])->toBe(1)->and($second['expiring'])->toBe(0)
        ->and($soon->fresh()?->expiry_warned_at)->not->toBeNull()
        ->and($later->fresh()?->expiry_warned_at)->toBeNull();
    Event::assertDispatchedTimes(MembershipExpiringSoon::class, 1);
});

it('warns once when the balance runs low', function (): void {
    Event::fake([LowCreditBalance::class, MembershipExpiringSoon::class]);
    $settings = app(BookingPolicySettings::class);
    $settings->low_balance_threshold = 1;
    $settings->save();
    $low = soldMembership(package: ['credits' => Credits::of(3)]);
    $fine = soldMembership(package: ['credits' => Credits::of(3)]);
    app(AdjustCredits::class)->handle($low, Credits::of(-2), 'used', null);

    expect(app(WarnExpiringMemberships::class)->handle()['low_balance'])->toBe(1)
        ->and(app(WarnExpiringMemberships::class)->handle()['low_balance'])->toBe(0)
        ->and($fine->fresh()?->low_balance_warned_at)->toBeNull();
    Event::assertDispatched(LowCreditBalance::class, fn (LowCreditBalance $e): bool => $e->membershipId === $low->id);
});

it('runs the membership jobs at the times of spec section 8', function (string $name, string $cron): void {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($e): bool => $e->description === $name);

    expect($event?->expression)->toBe($cron)
        ->and($event?->timezone)->toBe('Europe/Athens')
        ->and($event?->withoutOverlapping)->toBeTrue();
})->with([
    ['expire-memberships', '10 0 * * *'],
    ['warn-expiring-memberships', '0 10 * * *'],
    ['resume-frozen-memberships', '5 0 * * *'],
]);
