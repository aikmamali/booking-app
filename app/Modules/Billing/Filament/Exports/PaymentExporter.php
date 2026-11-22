<?php

declare(strict_types=1);

namespace App\Modules\Billing\Filament\Exports;

use App\Filament\Support\BaseExporter;
use App\Modules\Billing\Models\Payment;
use App\Shared\BusinessTime;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Builder;

final class PaymentExporter extends BaseExporter
{
    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('paid_at')->label(__('billing.fields.paid_at'))
                ->state(fn (Payment $record): string => $record->paid_at === null ? '' : app(BusinessTime::class)->toLocal($record->paid_at)->format('d/m/Y H:i')),
            ExportColumn::make('customer')->label(__('billing.fields.customer'))
                ->state(fn (Payment $record): string => $record->user->fullName()),
            ExportColumn::make('amount')->label(__('billing.fields.amount'))
                ->state(fn (Payment $record): string => $record->amount()->toDecimal()),
            ExportColumn::make('method')->label(__('billing.fields.method'))
                ->formatStateUsing(fn (Payment $record): string => $record->method->label()),
            ExportColumn::make('payable_type')->label(__('billing.fields.for'))
                ->formatStateUsing(fn (Payment $record): string => __('billing.payables.'.($record->payable_type ?? 'other'))),
            ExportColumn::make('status')->label(__('billing.fields.status'))
                ->formatStateUsing(fn (Payment $record): string => $record->status->label()),
            ExportColumn::make('recorded_by')->label(__('billing.fields.recorded_by'))
                ->state(fn (Payment $record): string => $record->recorder?->fullName() ?? ''),
            ExportColumn::make('note')->label(__('billing.fields.note')),
        ];
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['user', 'recorder']);
    }
}
