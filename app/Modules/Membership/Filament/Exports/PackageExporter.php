<?php

declare(strict_types=1);

namespace App\Modules\Membership\Filament\Exports;

use App\Filament\Support\BaseExporter;
use App\Modules\Membership\Models\Package;
use Filament\Actions\Exports\ExportColumn;

final class PackageExporter extends BaseExporter
{
    protected static ?string $model = Package::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label(__('membership.fields.name')),
            ExportColumn::make('credits')->label(__('membership.fields.credits'))
                ->state(fn (Package $record): string => $record->credits->toDecimal()),
            ExportColumn::make('validity_days')->label(__('membership.fields.validity_days')),
            ExportColumn::make('price')->label(__('membership.fields.price'))
                ->state(fn (Package $record): string => $record->price()->toDecimal()),
            ExportColumn::make('vat_rate')->label(__('membership.fields.vat_rate')),
            ExportColumn::make('is_active')->label(__('membership.fields.is_active')),
        ];
    }
}
