<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\Season;
use Illuminate\Support\Facades\DB;

/**
 * FS-01. Seasons may not overlap, so every date belongs to at most one season.
 */
final readonly class SaveSeason
{
    public function handle(string $name, string $startsOn, string $endsOn, ?Season $season = null): Season
    {
        if (trim($name) === '' || $endsOn <= $startsOn) {
            throw BookingRejected::because('booking.seasons.invalid');
        }

        $overlaps = Season::query()
            ->when($season instanceof Season, fn ($q) => $q->whereKeyNot($season?->id))
            ->whereDate('starts_on', '<=', $endsOn)->whereDate('ends_on', '>=', $startsOn)
            ->exists();

        if ($overlaps) {
            throw BookingRejected::because('booking.seasons.overlap');
        }

        return DB::transaction(function () use ($name, $startsOn, $endsOn, $season): Season {
            $season ??= new Season;
            $season->fill(['name' => trim($name), 'starts_on' => $startsOn, 'ends_on' => $endsOn])->save();

            return $season;
        });
    }
}
