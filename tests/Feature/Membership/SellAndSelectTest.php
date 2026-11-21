<?php

declare(strict_types=1);

use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Catalog\Models\Service;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Actions\SellPackage;
use App\Modules\Membership\Contracts\MembershipSelector;
use App\Modules\Membership\Data\SaleData;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Events\MembershipSold;
use App\Modules\Membership\Exceptions\LedgerRejected;
use App\Modules\Membership\Models\Package;
use App\Shared\Credits;
use Illuminate\Support\Facades\Event;

beforeEach(fn () => $this->travelTo('2027-03-20 08:00:00'));

it('sells a package with a snapshot of its terms', function (): void {
    Event::fake([MembershipSold::class]);
    $service = Service::factory()->create();
    $customer = User::factory()->customer()->create();

    $membership = soldMembership($customer, ['validity_days' => 30, 'price_cents' => 7000, 'credits' => Credits::of(4)], '2027-03-25', $service);

    expect($membership->status)->toBe(MembershipStatus::Active)
        ->and($membership->starts_on->toDateString())->toBe('2027-03-25')
        ->and($membership->ends_on->toDateString())->toBe('2027-04-23')
        ->and($membership->credits->toDecimal())->toBe('4.00')
        ->and($membership->price_cents)->toBe(7000)
        ->and($membership->service_ids)->toBe([$service->id])
        ->and($membership->getTranslation('package_name', 'el'))->toBe('10 μαθήματα');
    Event::assertDispatched(MembershipSold::class, fn (MembershipSold $e): bool => $e->priceCents === 7000 && $e->method === PaymentMethod::Cash && $e->userId === $customer->id);
});

it('keeps the sold terms when the package changes later', function (): void {
    $membership = soldMembership(package: ['credits' => Credits::of(10), 'price_cents' => 15000]);

    $membership->package->update(['credits' => Credits::of(5), 'price_cents' => 1]);
    $membership->package->services()->sync([]);

    $membership->refresh();
    expect($membership->credits->toDecimal())->toBe('10.00')
        ->and($membership->price_cents)->toBe(15000)
        ->and($membership->service_ids)->toHaveCount(1);
});

it('refuses invalid sales', function (Closure $sale, string $error): void {
    expect(fn () => app(SellPackage::class)->handle($sale()))->toThrow(LedgerRejected::class, $error);
})->with([
    'not a customer' => [fn (): SaleData => new SaleData(User::factory()->instructor()->create()->id, Package::factory()->create()->id, '2027-03-20', PaymentMethod::Cash), 'membership.errors.not_a_customer'],
    'anonymised customer' => [fn (): SaleData => new SaleData(User::factory()->customer()->create(['anonymized_at' => now()])->id, Package::factory()->create()->id, '2027-03-20', PaymentMethod::Cash), 'membership.errors.not_a_customer'],
    'inactive package' => [fn (): SaleData => new SaleData(User::factory()->customer()->create()->id, Package::factory()->create(['is_active' => false])->id, '2027-03-20', PaymentMethod::Cash), 'membership.errors.package_inactive'],
    'start in the past' => [fn (): SaleData => new SaleData(User::factory()->customer()->create()->id, Package::factory()->create()->id, '2027-03-19', PaymentMethod::Cash), 'membership.errors.starts_in_past'],
]);

it('charges the membership that expires first (FEFO)', function (): void {
    $customer = User::factory()->customer()->create();
    $service = Service::factory()->create();
    $later = soldMembership($customer, ['validity_days' => 90], '2027-03-20', $service);
    $sooner = soldMembership($customer, ['validity_days' => 30], '2027-03-20', $service);

    $choice = app(MembershipSelector::class)->choose($customer->id, $service->id, Credits::of(1), '2027-03-23');

    expect($choice->membershipId)->toBe($sooner->id)->not->toBe($later->id);
});

it('skips memberships that cannot pay', function (string $case): void {
    $customer = User::factory()->customer()->create();
    $service = Service::factory()->create();
    $good = soldMembership($customer, ['validity_days' => 90], '2027-03-20', $service);

    $bad = match ($case) {
        'other service' => soldMembership($customer, ['validity_days' => 10], '2027-03-20'),
        'not started' => soldMembership($customer, ['validity_days' => 10], '2027-03-24', $service),
        'expired status' => tap(soldMembership($customer, ['validity_days' => 10], '2027-03-20', $service))->update(['status' => MembershipStatus::Expired]),
        'too few credits' => soldMembership($customer, ['validity_days' => 10, 'credits' => Credits::of('0.5')], '2027-03-20', $service),
    };

    $choice = app(MembershipSelector::class)->choose($customer->id, $service->id, Credits::of(1), '2027-03-23');

    expect($choice->membershipId)->toBe($good->id)->not->toBe($bad->id);
})->with(['other service', 'not started', 'expired status', 'too few credits']);

it('says why no membership can pay', function (): void {
    $customer = User::factory()->customer()->create();
    $service = Service::factory()->create();
    $selector = app(MembershipSelector::class);

    expect($selector->choose($customer->id, $service->id, Credits::of(1), '2027-03-23')->rejection)->toBe('booking.no_membership');

    soldMembership($customer, ['credits' => Credits::of('0.5')], '2027-03-20', $service);

    $choice = $selector->choose($customer->id, $service->id, Credits::of(1), '2027-03-23');
    expect($choice->isFound())->toBeFalse()
        ->and($choice->rejection)->toBe('booking.insufficient_credits')
        ->and($selector->choose($customer->id, $service->id, Credits::of('0.5'), '2027-03-23')->isFound())->toBeTrue();
});

it('only counts a membership on the days it is valid', function (): void {
    $customer = User::factory()->customer()->create();
    $service = Service::factory()->create();
    soldMembership($customer, ['validity_days' => 7], '2027-03-20', $service); // 20–26 March

    $on = fn (string $date): bool => app(MembershipSelector::class)->choose($customer->id, $service->id, Credits::of(1), $date)->isFound();

    expect($on('2027-03-20'))->toBeTrue()->and($on('2027-03-26'))->toBeTrue()->and($on('2027-03-27'))->toBeFalse();
});
