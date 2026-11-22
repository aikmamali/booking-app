<?php

declare(strict_types=1);

namespace App\Modules\Booking\Filament\Exports;

use App\Filament\Support\BaseExporter;
use App\Modules\Booking\Models\Booking;
use App\Shared\BusinessTime;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Builder;

final class BookingExporter extends BaseExporter
{
    protected static ?string $model = Booking::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('reference')->label(__('booking.fields.reference'))
                ->state(fn (Booking $record): string => $record->reference()),
            ExportColumn::make('when')->label(__('booking.fields.when'))
                ->state(fn (Booking $record): string => app(BusinessTime::class)->toLocal($record->session->starts_at)->format('d/m/Y H:i')),
            ExportColumn::make('session.service.name')->label(__('catalog.service.label')),
            ExportColumn::make('customer')->label(__('booking.fields.customer'))
                ->state(fn (Booking $record): string => $record->user->fullName()),
            ExportColumn::make('user.email')->label(__('identity.fields.email')),
            ExportColumn::make('status')->label(__('booking.fields.status'))
                ->formatStateUsing(fn (Booking $record): string => $record->status->label()),
            ExportColumn::make('source')->label(__('booking.fields.source'))
                ->formatStateUsing(fn (Booking $record): string => __('booking.sources.'.$record->source->value)),
            ExportColumn::make('credits_charged')->label(__('booking.fields.cost'))
                ->state(fn (Booking $record): string => $record->credits_charged->toDecimal()),
            ExportColumn::make('cancel_reason')->label(__('booking.fields.cancel_reason')),
        ];
    }

    /**
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['user', 'session.service']);
    }
}
