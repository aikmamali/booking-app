<?php

declare(strict_types=1);

namespace App\Modules\Communication\Filament\Resources\NoteResource\Pages;

use App\Modules\Communication\Filament\Resources\NoteResource;
use Filament\Resources\Pages\ManageRecords;

final class ManageNotes extends ManageRecords
{
    protected static string $resource = NoteResource::class;

    protected function getHeaderActions(): array
    {
        return [NoteResource::createAction()];
    }
}
