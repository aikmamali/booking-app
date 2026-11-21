<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Membership\Models\Package;
use App\Shared\Credits;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    /**
     * @return array<model-property<Package>, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ['el' => '10 μαθήματα', 'en' => '10 classes'],
            'credits' => Credits::of(10),
            'validity_days' => 90,
            'price_cents' => 15000,
            'vat_rate' => 24,
            'is_purchasable_online' => false,
            'is_active' => true,
            'position' => 1,
        ];
    }
}
