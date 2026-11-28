<?php

declare(strict_types=1);

namespace App\Modules\Billing\Filament\Exports;

use App\Filament\Support\BaseExporter;
use App\Modules\Billing\Models\Expense;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Builder;

final class ExpenseExporter extends BaseExporter
{
    protected static ?string $model = Expense::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('spent_on')->label(__('billing.fields.spent_on'))
                ->state(fn (Expense $record): string => $record->spent_on->format('d/m/Y')),
            ExportColumn::make('category')->label(__('billing.fields.category'))
                ->formatStateUsing(fn (Expense $record): string => $record->category->label()),
            ExportColumn::make('amount')->label(__('billing.fields.amount'))
                ->state(fn (Expense $record): string => $record->amount()->toDecimal()),
            ExportColumn::make('note')->label(__('billing.fields.note')),
            ExportColumn::make('recorded_by')->label(__('billing.fields.recorded_by'))
                ->state(fn (Expense $record): string => $record->recorder?->fullName() ?? ''),
        ];
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with('recorder');
    }
}
