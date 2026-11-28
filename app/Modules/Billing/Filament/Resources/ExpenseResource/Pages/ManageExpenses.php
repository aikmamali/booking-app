<?php

declare(strict_types=1);

namespace App\Modules\Billing\Filament\Resources\ExpenseResource\Pages;

use App\Modules\Billing\Filament\Exports\ExpenseExporter;
use App\Modules\Billing\Filament\Resources\ExpenseResource;
use Filament\Resources\Pages\ManageRecords;

final class ManageExpenses extends ManageRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [ExpenseResource::createAction(), ExpenseExporter::action()];
    }
}
