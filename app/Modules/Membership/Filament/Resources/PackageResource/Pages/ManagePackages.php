<?php

declare(strict_types=1);

namespace App\Modules\Membership\Filament\Resources\PackageResource\Pages;

use App\Modules\Membership\Filament\Exports\PackageExporter;
use App\Modules\Membership\Filament\Resources\PackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

final class ManagePackages extends ManageRecords
{
    protected static string $resource = PackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(fn (array $data, CreateAction $action): Model => PackageResource::save(null, $data, $action)),
            PackageExporter::action(),
        ];
    }
}
