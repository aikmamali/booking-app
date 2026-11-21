<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filament\Exports;

use App\Filament\Support\BaseExporter;
use App\Modules\Catalog\Models\Service;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Builder;

final class ServiceExporter extends BaseExporter
{
    protected static ?string $model = Service::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label(__('catalog.fields.name')),
            ExportColumn::make('category.name')->label(__('catalog.category.label')),
            ExportColumn::make('duration_minutes')->label(__('catalog.fields.duration_minutes')),
            ExportColumn::make('default_capacity')->label(__('catalog.fields.default_capacity')),
            ExportColumn::make('credit_cost')->label(__('catalog.fields.credit_cost'))
                ->state(fn (Service $record): string => $record->credit_cost->toDecimal()),
            ExportColumn::make('drop_in_price')->label(__('catalog.fields.drop_in_price'))
                ->state(fn (Service $record): string => $record->dropInPrice()?->toDecimal() ?? ''),
            ExportColumn::make('is_active')->label(__('catalog.fields.is_active')),
        ];
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with('category');
    }
}
