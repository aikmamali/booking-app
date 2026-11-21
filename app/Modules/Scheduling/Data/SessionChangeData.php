<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Data;

use Spatie\LaravelData\Data;

/**
 * SC-03: edits to one session. Date and time are local to the business time zone.
 * lock_version is the version the editor loaded, for optimistic locking.
 */
final class SessionChangeData extends Data
{
    public function __construct(
        public int $instructor_id,
        public ?int $room_id,
        public int $capacity,
        public string $date,
        public string $start_time,
        public int $lock_version,
    ) {}
}
