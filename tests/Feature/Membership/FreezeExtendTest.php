<?php

declare(strict_types=1);

use App\Modules\Identity\Models\User;
use App\Modules\Membership\Actions\ExpireMemberships;
use App\Modules\Membership\Actions\ExtendMembership;
use App\Modules\Membership\Actions\FreezeMembership;
use App\Modules\Membership\Actions\ResumeFrozenMemberships;
use App\Modules\Membership\Actions\ResumeMembership;
use App\Modules\Membership\Contracts\MembershipSelector;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Exceptions\LedgerRejected;
use App\Modules\Membership\Filament\Resources\MembershipResource\Pages\ListMemberships;
use App\Modules\Membership\Models\Membership;
use App\Shared\Credits;
use Livewire\Livewire;

// 10:00 Athens on 20/3/2027. Sold today with 90 days validity: ends 17/6/2027.
beforeEach(fn () => $this->travelTo('2027-03-20 08:00:00'));

function freezeUntil(Membership $m, string $until, ?int $actor = null): Membership
{
    return app(FreezeMembership::class)->handle($m, $until, 'Τραυματισμός', $actor);
}

it('freezes until a date and moves the end by the paused days', function (): void {
    $admin = User::factory()->admin()->create();
    $m = soldMembership();

    $m = freezeUntil($m, '2027-04-03', $admin->id);

    expect($m->status)->toBe(MembershipStatus::Frozen)
        ->and($m->frozen_until?->toDateString())->toBe('2027-04-03')
        ->and($m->ends_on->toDateString())->toBe('2027-07-01');
    $this->assertDatabaseHas('activity_log', ['description' => 'membership_frozen', 'subject_id' => $m->id, 'causer_id' => $admin->id]);
});

it('does not charge a frozen membership', function (): void {
    $m = freezeUntil(soldMembership(), '2027-04-03');

    $choice = app(MembershipSelector::class)->choose($m->user_id, $m->service_ids[0], Credits::of(1), '2027-03-25');

    expect($choice->membershipId)->toBeNull();
});

it('refuses a freeze that ends today or earlier, without a reason, or of a non-active membership', function (): void {
    $m = soldMembership();

    expect(fn (): Membership => freezeUntil($m, '2027-03-20'))->toThrow(LedgerRejected::class, 'membership.errors.freeze_until_past')
        ->and(fn () => app(FreezeMembership::class)->handle($m, '2027-04-01', ' ', null))->toThrow(LedgerRejected::class, 'membership.errors.reason_required');

    freezeUntil($m, '2027-04-01');
    expect(fn (): Membership => freezeUntil($m->refresh(), '2027-04-10'))->toThrow(LedgerRejected::class, 'membership.errors.not_active');
});

it('resumes early and gives back the unused paused days', function (): void {
    $m = freezeUntil(soldMembership(), '2027-04-03'); // +14 days → 1/7

    $this->travelTo('2027-03-25 08:00:00'); // 9 days unused
    $m = app(ResumeMembership::class)->handle($m, null);

    expect($m->status)->toBe(MembershipStatus::Active)
        ->and($m->frozen_until)->toBeNull()
        ->and($m->ends_on->toDateString())->toBe('2027-06-22');
});

it('refuses to resume a membership that is not frozen', function (): void {
    app(ResumeMembership::class)->handle(soldMembership(), null);
})->throws(LedgerRejected::class, 'membership.errors.not_frozen');

it('resumes by itself on the frozen_until day, keeping the full extension', function (): void {
    $m = freezeUntil(soldMembership(), '2027-04-03');

    $this->travelTo('2027-04-02 20:59:00'); // 23:59 Athens on 2/4
    expect(app(ResumeFrozenMemberships::class)->handle())->toBe(0);

    $this->travelTo('2027-04-02 21:05:00'); // 00:05 Athens on 3/4
    expect(app(ResumeFrozenMemberships::class)->handle())->toBe(1)
        ->and(app(ResumeFrozenMemberships::class)->handle())->toBe(0);

    $m->refresh();
    expect($m->status)->toBe(MembershipStatus::Active)
        ->and($m->ends_on->toDateString())->toBe('2027-07-01');
});

it('does not expire a frozen membership before its moved end date', function (): void {
    $m = freezeUntil(soldMembership(), '2027-04-03');

    $this->travelTo('2027-06-20 08:00:00'); // after the original end
    app(ExpireMemberships::class)->handle();

    expect($m->refresh()->status)->toBe(MembershipStatus::Frozen);
});

it('extends an active or frozen membership with a reason', function (): void {
    $m = app(ExtendMembership::class)->handle(soldMembership(), 10, 'Αργία γυμναστηρίου', null);

    expect($m->ends_on->toDateString())->toBe('2027-06-27');
    $this->assertDatabaseHas('activity_log', ['description' => 'membership_extended', 'subject_id' => $m->id]);

    $frozen = freezeUntil(soldMembership(), '2027-03-25');
    expect(app(ExtendMembership::class)->handle($frozen, 1, 'x', null)->ends_on->toDateString())->toBe('2027-06-23');
});

it('refuses extensions without days, without a reason, or of an expired membership', function (): void {
    $m = soldMembership();
    $extend = app(ExtendMembership::class);

    expect(fn () => $extend->handle($m, 0, 'x', null))->toThrow(LedgerRejected::class, 'membership.errors.extend_days')
        ->and(fn () => $extend->handle($m, 5, '', null))->toThrow(LedgerRejected::class, 'membership.errors.reason_required');

    $m->forceFill(['status' => MembershipStatus::Expired])->save();
    expect(fn () => $extend->handle($m, 5, 'x', null))->toThrow(LedgerRejected::class, 'membership.errors.not_active');
});

it('offers freeze, resume and extend in the admin', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $m = soldMembership();

    Livewire::test(ListMemberships::class)
        ->assertTableActionVisible('freeze', $m)
        ->assertTableActionHidden('resume', $m)
        ->callTableAction('freeze', $m, ['until' => '2027-04-03', 'reason' => 'Τραυματισμός'])
        ->assertHasNoTableActionErrors();

    expect($m->refresh()->status)->toBe(MembershipStatus::Frozen);

    Livewire::test(ListMemberships::class)->filterTable('status', MembershipStatus::Frozen->value)
        ->callTableAction('extend', $m, ['days' => 3, 'reason' => 'Δώρο'])
        ->callTableAction('resume', $m);

    expect($m->refresh()->status)->toBe(MembershipStatus::Active)
        ->and($m->ends_on->toDateString())->toBe('2027-06-20');
});

it('shows the customer until when the membership is paused', function (): void {
    $m = freezeUntil(soldMembership(), '2027-04-03');

    $this->actingAs($m->user)->get(route('app.membership'))
        ->assertOk()->assertSee('03/04/2027');
});
