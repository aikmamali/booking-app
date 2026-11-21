<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filament\Resources\ServiceCategoryResource\Pages;

use App\Modules\Catalog\Actions\SaveServiceCategory;
use App\Modules\Catalog\Filament\Resources\ServiceCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

final class ManageServiceCategories extends ManageRecords
{
    protected static string $resource = ServiceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data): Model => app(SaveServiceCategory::class)->handle(null, $data['name'])),
        ];
    }
}
