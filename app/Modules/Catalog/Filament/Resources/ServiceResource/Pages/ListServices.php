<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filament\Resources\ServiceResource\Pages;

use App\Modules\Catalog\Filament\Exports\ServiceExporter;
use App\Modules\Catalog\Filament\Resources\ServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make(), ServiceExporter::action()];
    }
}
