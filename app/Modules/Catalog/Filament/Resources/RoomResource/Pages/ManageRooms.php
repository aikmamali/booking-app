<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filament\Resources\RoomResource\Pages;

use App\Modules\Catalog\Filament\Resources\RoomResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

final class ManageRooms extends ManageRecords
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data): Model => RoomResource::save(null, $data))];
    }
}
