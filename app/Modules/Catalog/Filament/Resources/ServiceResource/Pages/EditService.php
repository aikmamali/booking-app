<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filament\Resources\ServiceResource\Pages;

use App\Modules\Catalog\Actions\SaveService;
use App\Modules\Catalog\Filament\Resources\ServiceResource;
use App\Modules\Catalog\Models\Service;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Service $service */
        $service = $this->getRecord();

        return ServiceResource::toFormState($service);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Service $record */
        return app(SaveService::class)->handle($record, ServiceResource::toData($data));
    }
}
