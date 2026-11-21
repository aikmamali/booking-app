<?php

declare(strict_types=1);

use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Catalog\Models\Service;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Actions\SellPackage;
use App\Modules\Membership\Data\SaleData;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\Package;
use App\Shared\Credits;

/**
 * Sells a package through the real action, so the membership has its purchase entry.
 *
 * @param  array<string, mixed>  $package
 */
function soldMembership(?User $customer = null, array $package = [], ?string $startsOn = null, ?Service $service = null): Membership
{
    $service ??= Service::factory()->create();
    $pkg = Package::factory()->create(['credits' => Credits::of(10), ...$package]);
    $pkg->services()->sync([$service->id]);

    return app(SellPackage::class)->handle(new SaleData(
        user_id: ($customer ?? User::factory()->customer()->create())->id,
        package_id: $pkg->id,
        starts_on: $startsOn ?? now()->setTimezone('Europe/Athens')->toDateString(),
        payment_method: PaymentMethod::Cash,
    ));
}
