<?php

declare(strict_types=1);

namespace App\Modules\Booking\Filament\Resources\FixedSeatResource\Pages;

use App\Modules\Booking\Filament\Resources\FixedSeatResource;
use Filament\Resources\Pages\ListRecords;

final class ListFixedSeats extends ListRecords
{
    protected static string $resource = FixedSeatResource::class;

    protected function getHeaderActions(): array
    {
        return [FixedSeatResource::assignAction()];
    }
}
