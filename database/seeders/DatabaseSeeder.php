<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo data for local and staging (spec section 10.4). Never run in production.
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'first_name' => 'Διαχειριστής',
            'last_name' => 'Demo',
            'email' => 'admin@booking.test',
            'phone' => null,
        ]);

        User::factory()->instructor()->count(2)->create();

        User::factory()->customer()->create([
            'first_name' => 'Πελάτης',
            'last_name' => 'Demo',
            'email' => 'customer@booking.test',
        ]);

        User::factory()->customer()->count(19)->create();
    }
}
