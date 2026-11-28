<?php

declare(strict_types=1);

namespace App\Modules\Communication\Filament\Resources\BroadcastResource\Pages;

use App\Modules\Communication\Filament\Resources\BroadcastResource;
use Filament\Resources\Pages\ListRecords;

final class ListBroadcasts extends ListRecords
{
    protected static string $resource = BroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [BroadcastResource::sendAction()];
    }
}
