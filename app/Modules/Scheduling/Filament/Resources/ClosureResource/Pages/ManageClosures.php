<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Filament\Resources\ClosureResource\Pages;

use App\Modules\Scheduling\Filament\Resources\ClosureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

final class ManageClosures extends ManageRecords
{
    protected static string $resource = ClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(fn (array $data, CreateAction $action): Model => ClosureResource::save(null, $data, $action)),
        ];
    }
}
