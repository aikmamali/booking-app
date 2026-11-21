<?php

declare(strict_types=1);

namespace App\Modules\Communication\Filament\Resources\AnnouncementResource\Pages;

use App\Modules\Communication\Filament\Resources\AnnouncementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

final class ManageAnnouncements extends ManageRecords
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data): Model => AnnouncementResource::save(null, $data))];
    }
}
