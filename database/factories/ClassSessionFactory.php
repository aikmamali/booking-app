<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Catalog\Models\Service;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Enums\SessionStatus;
use App\Modules\Scheduling\Models\ClassSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSession>
 */
class ClassSessionFactory extends Factory
{
    protected $model = ClassSession::class;

    /**
     * @return array<model-property<ClassSession>, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays(2)->setTime(16, 30)->toImmutable();

        return [
            'schedule_id' => null,
            'service_id' => Service::factory(),
            'instructor_id' => User::factory()->instructor(),
            'room_id' => null,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addHour(),
            'capacity' => 5,
            'status' => SessionStatus::Scheduled,
        ];
    }
}
