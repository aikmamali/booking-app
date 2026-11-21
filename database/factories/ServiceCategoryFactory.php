<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceCategory>
 */
class ServiceCategoryFactory extends Factory
{
    protected $model = ServiceCategory::class;

    /**
     * @return array<model-property<ServiceCategory>, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ['el' => 'Ομαδικά', 'en' => 'Group classes'],
            'position' => 1,
        ];
    }
}
