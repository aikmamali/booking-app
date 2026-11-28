<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\FixedSeatStatus;
use App\Modules\Booking\Models\FixedSeat;
use App\Shared\BusinessTime;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * FS-01: once a season is over its seats end, and open requests lapse. Idempotent.
 */
final readonly class EndFinishedSeasons
{
    public function __construct(
        private BusinessTime $time,
        private Clock $clock,
    ) {}

    public function handle(): int
    {
        return DB::transaction(fn (): int => FixedSeat::query()
            ->whereIn('status', [FixedSeatStatus::Requested, FixedSeatStatus::Active])
            ->whereHas('season', fn ($q) => $q->whereDate('ends_on', '<', $this->time->today()))
            ->update(['status' => FixedSeatStatus::Ended, 'updated_at' => $this->clock->now()]));
    }
}
