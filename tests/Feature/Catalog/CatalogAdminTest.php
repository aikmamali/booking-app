<?php

declare(strict_types=1);

use App\Modules\Catalog\Filament\Resources\RoomResource\Pages\ManageRooms;
use App\Modules\Catalog\Filament\Resources\ServiceCategoryResource\Pages\ManageServiceCategories;
use App\Modules\Catalog\Filament\Resources\ServiceResource\Pages\CreateService;
use App\Modules\Catalog\Filament\Resources\ServiceResource\Pages\EditService;
use App\Modules\Catalog\Filament\Resources\ServiceResource\Pages\ListServices;
use App\Modules\Catalog\Models\Room;
use App\Modules\Catalog\Models\Service;
use App\Modules\Catalog\Models\ServiceCategory;
use App\Modules\Identity\Models\User;
use Filament\Actions\CreateAction;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

function serviceForm(ServiceCategory $category, array $overrides = []): array
{
    return [
        'category_id' => $category->id,
        'name' => ['el' => 'Πιλάτες', 'en' => 'Pilates'],
        'description' => ['el' => 'Ήπια ενδυνάμωση', 'en' => null],
        'duration_minutes' => 50,
        'default_capacity' => 4,
        'credit_cost' => '0.5',
        'drop_in_price' => '15.50',
        'is_active' => true,
        'policy_overrides' => ['cancellation_cutoff_minutes' => '120', 'late_cancel_allowed' => '1'],
        ...$overrides,
    ];
}

it('creates a category with Greek and English names', function (): void {
    Livewire::test(ManageServiceCategories::class)
        ->callAction(CreateAction::class, ['name' => ['el' => 'Ομαδικά', 'en' => 'Group']])
        ->assertHasNoActionErrors();

    $category = ServiceCategory::query()->sole();
    expect($category->getTranslations('name'))->toBe(['el' => 'Ομαδικά', 'en' => 'Group'])
        ->and($category->name)->toBe('Ομαδικά');
});

it('requires the Greek name of a category', function (): void {
    Livewire::test(ManageServiceCategories::class)
        ->callAction(CreateAction::class, ['name' => ['el' => '', 'en' => 'Group']])
        ->assertHasActionErrors(['name.el' => 'required']);
});

it('creates a service with half-credit cost, drop-in price and rule exceptions', function (): void {
    $category = ServiceCategory::factory()->create();

    Livewire::test(CreateService::class)->fillForm(serviceForm($category))->call('create')->assertHasNoFormErrors();

    $service = Service::query()->sole();
    expect($service->getTranslations('name'))->toBe(['el' => 'Πιλάτες', 'en' => 'Pilates'])
        ->and($service->getTranslations('description'))->toBe(['el' => 'Ήπια ενδυνάμωση'])
        ->and($service->credit_cost->toDecimal())->toBe('0.50')
        ->and($service->drop_in_price_cents)->toBe(1550)
        ->and($service->policy_overrides?->cancellation_cutoff_minutes)->toBe(120)
        ->and($service->policy_overrides?->late_cancel_allowed)->toBeTrue()
        ->and($service->policy_overrides?->booking_opens_days_ahead)->toBeNull();
});

it('stores no overrides when none are given', function (): void {
    Livewire::test(CreateService::class)
        ->fillForm(serviceForm(ServiceCategory::factory()->create(), ['policy_overrides' => [], 'drop_in_price' => null]))
        ->call('create')->assertHasNoFormErrors();

    $service = Service::query()->sole();
    expect($service->policy_overrides)->toBeNull()->and($service->dropInPrice())->toBeNull();
});

it('rejects credit costs that are not multiples of 0.5', function (string $cost): void {
    Livewire::test(CreateService::class)
        ->fillForm(serviceForm(ServiceCategory::factory()->create(), ['credit_cost' => $cost]))
        ->call('create')
        ->assertHasFormErrors(['credit_cost']);
})->with(['0.3', '0', '1.25']);

it('fills the edit form back from the stored service and saves changes', function (): void {
    Livewire::test(CreateService::class)->fillForm(serviceForm(ServiceCategory::factory()->create()))->call('create');
    $service = Service::query()->sole();

    Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
        ->assertFormSet([
            'name' => ['el' => 'Πιλάτες', 'en' => 'Pilates'],
            'credit_cost' => '0.50',
            'drop_in_price' => '15.50',
            'policy_overrides.cancellation_cutoff_minutes' => 120,
            'policy_overrides.late_cancel_allowed' => '1',
        ])
        ->fillForm(['credit_cost' => '1.5', 'policy_overrides.late_cancel_allowed' => ''])
        ->call('save')
        ->assertHasNoFormErrors();

    $service->refresh();
    expect($service->credit_cost->toDecimal())->toBe('1.50')
        ->and($service->policy_overrides?->late_cancel_allowed)->toBeNull()
        ->and($service->policy_overrides?->cancellation_cutoff_minutes)->toBe(120);
});

it('lists services and soft-deletes them', function (): void {
    $service = Service::factory()->create();

    Livewire::test(ListServices::class)
        ->assertCanSeeTableRecords([$service])
        ->callTableAction('delete', $service);

    expect(Service::query()->count())->toBe(0)
        ->and(Service::withTrashed()->count())->toBe(1);
});

it('manages rooms', function (): void {
    Livewire::test(ManageRooms::class)
        ->callAction(CreateAction::class, ['name' => 'Αίθουσα 1', 'capacity' => 8])
        ->assertHasNoActionErrors();

    expect(Room::query()->sole()->only(['name', 'capacity']))->toBe(['name' => 'Αίθουσα 1', 'capacity' => 8]);
});

it('keeps customers and instructors out of the catalogue', function (string $role): void {
    $this->actingAs(User::factory()->{$role}()->create());

    $this->get('/admin/services')->assertForbidden();
})->with(['customer', 'instructor']);
