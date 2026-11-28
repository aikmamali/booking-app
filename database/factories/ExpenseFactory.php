<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Billing\Enums\ExpenseCategory;
use App\Modules\Billing\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'category' => fake()->randomElement(ExpenseCategory::cases()),
            'amount_cents' => fake()->numberBetween(1_000, 150_000),
            'spent_on' => now()->subDays(fake()->numberBetween(0, 60))->toDateString(),
            'note' => null,
        ];
    }
}
