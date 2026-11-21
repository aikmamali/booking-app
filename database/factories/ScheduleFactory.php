<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Models\Service;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Enums\Weekday;
use App\Modules\Scheduling\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    /**
     * @return array<model-property<Schedule>, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'instructor_id' => User::factory()->instructor(),
            'room_id' => null,
            'weekday' => Weekday::Tuesday,
            'start_time' => '18:30',
            'capacity' => 5,
            'valid_from' => now()->toDateString(),
            'valid_until' => null,
        ];
    }
}
