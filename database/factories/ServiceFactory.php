<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Models\Service;
use App\Modules\Catalog\Models\ServiceCategory;
use App\Shared\Credits;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * @return array<model-property<Service>, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => ServiceCategory::factory(),
            'name' => ['el' => 'Group Training', 'en' => 'Group Training'],
            'description' => ['el' => 'Ομαδική προπόνηση έως 5 ατόμων.'],
            'duration_minutes' => 60,
            'default_capacity' => 5,
            'credit_cost' => Credits::of(1),
            'drop_in_price_cents' => null,
            'is_active' => true,
            'position' => 1,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
