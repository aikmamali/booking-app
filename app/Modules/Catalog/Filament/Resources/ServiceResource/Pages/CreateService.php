<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filament\Resources\ServiceResource\Pages;

use App\Modules\Catalog\Actions\SaveService;
use App\Modules\Catalog\Filament\Resources\ServiceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveService::class)->handle(null, ServiceResource::toData($data));
    }
}
