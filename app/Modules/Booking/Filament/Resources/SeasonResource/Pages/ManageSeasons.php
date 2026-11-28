<?php

declare(strict_types=1);

namespace App\Modules\Booking\Filament\Resources\SeasonResource\Pages;

use App\Modules\Booking\Filament\Resources\SeasonResource;
use Filament\Resources\Pages\ManageRecords;

final class ManageSeasons extends ManageRecords
{
    protected static string $resource = SeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [SeasonResource::createAction()];
    }
}
