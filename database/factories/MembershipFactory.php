<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Identity\Models\User;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\Package;
use App\Shared\Credits;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A membership row without ledger entries. Tests that need credits should sell a package
 * through the SellPackage action instead.
 *
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    /**
     * @return array<model-property<Membership>, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->customer(),
            'package_id' => Package::factory(),
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addDays(89)->toDateString(),
            'status' => MembershipStatus::Active,
            'package_name' => ['el' => '10 μαθήματα'],
            'credits' => Credits::of(10),
            'price_cents' => 15000,
            'vat_rate' => 24,
            'validity_days' => 90,
            'service_ids' => [],
        ];
    }
}
