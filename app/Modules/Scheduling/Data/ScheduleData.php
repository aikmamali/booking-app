<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Data;

use App\Modules\Scheduling\Enums\Weekday;
use Spatie\LaravelData\Data;

final class ScheduleData extends Data
{
    public function __construct(
        public int $service_id,
        public int $instructor_id,
        public ?int $room_id,
        public Weekday $weekday,
        public string $start_time,
        public int $capacity,
        public string $valid_from,
        public ?string $valid_until,
    ) {}
}
