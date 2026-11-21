<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Service;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Contracts\CreditLedger;
use App\Modules\Membership\Events\MembershipSold;
use App\Modules\Membership\Filament\Resources\MembershipResource\Pages\ListMemberships;
use App\Modules\Membership\Filament\Resources\MembershipResource\Pages\ViewMembership;
use App\Modules\Membership\Filament\Resources\MembershipResource\RelationManagers\TransactionsRelationManager;
use App\Modules\Membership\Filament\Resources\PackageResource\Pages\ManagePackages;
use App\Modules\Membership\Livewire\MyMemberships;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\Package;
use App\Shared\Credits;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(fn () => $this->travelTo('2027-03-20 08:00:00'));

describe('admin', function (): void {
    beforeEach(fn () => $this->actingAs($this->admin = User::factory()->admin()->create()));

    it('creates a package with its services', function (): void {
        $services = Service::factory()->count(2)->create();

        Livewire::test(ManagePackages::class)
            ->callAction(CreateAction::class, [
                'name' => ['el' => '8 μαθήματα', 'en' => '8 classes'], 'credits' => '8', 'validity_days' => 60,
                'price' => '120.50', 'vat_rate' => 24, 'service_ids' => $services->modelKeys(), 'is_active' => true,
            ])
            ->assertHasNoActionErrors();

        $package = Package::query()->with('services')->sole();
        expect($package->credits->toDecimal())->toBe('8.00')
            ->and($package->price_cents)->toBe(12050)
            ->and($package->services->modelKeys())->toEqualCanonicalizing($services->modelKeys());
    });

    it('requires at least one service and half-credit steps', function (): void {
        Livewire::test(ManagePackages::class)
            ->callAction(CreateAction::class, [
                'name' => ['el' => 'Χ'], 'credits' => '1.2', 'validity_days' => 30, 'price' => '10', 'vat_rate' => 24, 'service_ids' => [],
            ])
            ->assertHasActionErrors(['credits', 'service_ids']);
    });

    it('sells a package at the desk', function (): void {
        Event::fake([MembershipSold::class]);
        $customer = User::factory()->customer()->create();
        $package = Package::factory()->create();
        $package->services()->sync([Service::factory()->create()->id]);

        Livewire::test(ListMemberships::class)
            ->callAction('sell', ['user_id' => $customer->id, 'package_id' => $package->id, 'starts_on' => '2027-03-20', 'payment_method' => 'pos'])
            ->assertHasNoActionErrors()
            ->assertNotified(__('membership.notices.sold'));

        $membership = Membership::query()->sole();
        expect($membership->user_id)->toBe($customer->id)
            ->and($membership->sold_by)->toBe($this->admin->id)
            ->and(app(CreditLedger::class)->balance($membership->id)->toDecimal())->toBe('10.00');
    });

    it('shows a sale rejection as a message', function (): void {
        Livewire::test(ListMemberships::class)
            ->callAction('sell', [
                'user_id' => User::factory()->instructor()->create()->id, 'package_id' => Package::factory()->create()->id,
                'starts_on' => '2027-03-20', 'payment_method' => 'cash',
            ])
            ->assertNotified(__('membership.errors.not_a_customer'));

        expect(Membership::query()->count())->toBe(0);
    });

    it('lists memberships with their balance and corrects credits', function (): void {
        $membership = soldMembership();

        Livewire::test(ListMemberships::class)
            ->assertCanSeeTableRecords([$membership])
            ->assertTableColumnFormattedStateSet('balance', '10', $membership->getKey())
            ->callTableAction('adjust', $membership, ['amount' => '-1.5', 'reason' => 'Διόρθωση'])
            ->assertHasNoTableActionErrors();

        expect(app(CreditLedger::class)->balance($membership->id)->toDecimal())->toBe('8.50');
    });

    it('shows the ledger history of a membership', function (): void {
        $membership = soldMembership();

        $this->get(ViewMembership::getUrl(['record' => $membership]))->assertOk();
        Livewire::test(TransactionsRelationManager::class, ['ownerRecord' => $membership, 'pageClass' => ViewMembership::class])
            ->assertCanSeeTableRecords($membership->transactions)
            ->assertTableActionDoesNotExist('create');
    });
});

it('keeps customers and instructors out of memberships and packages', function (string $role, string $url): void {
    $this->actingAs(User::factory()->{$role}()->create())->get($url)->assertForbidden();
})->with(['customer', 'instructor'])->with(['/admin/memberships', '/admin/packages']);

it('shows customers their own memberships, balance and history', function (): void {
    $customer = User::factory()->customer()->create();
    $mine = soldMembership($customer, ['credits' => Credits::of('7.5')]);
    soldMembership(); // someone else's

    $this->actingAs($customer)->get(route('app.membership'))
        ->assertOk()
        ->assertSee('7,5')
        ->assertSee('10 μαθήματα')
        ->assertSee(__('membership.transaction_types.purchase'));

    Livewire::actingAs($customer)->test(MyMemberships::class)
        ->assertViewHas('memberships', fn ($list): bool => $list->modelKeys() === [$mine->id]);
});

it('tells customers without a membership what to do', function (): void {
    $this->actingAs(User::factory()->customer()->create())->get(route('app.membership'))
        ->assertOk()->assertSee(__('membership.mine.empty'));
});

it('sends guests to the login page', function (): void {
    $this->get(route('app.membership'))->assertRedirect(route('app.login'));
});
