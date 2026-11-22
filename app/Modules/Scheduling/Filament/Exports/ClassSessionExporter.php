<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Filament\Exports;

use App\Filament\Support\BaseExporter;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\BusinessTime;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Builder;

final class ClassSessionExporter extends BaseExporter
{
    protected static ?string $model = ClassSession::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('starts_at')->label(__('scheduling.fields.starts_at'))
                ->state(fn (ClassSession $record): string => app(BusinessTime::class)->toLocal($record->starts_at)->format('d/m/Y H:i')),
            ExportColumn::make('service.name')->label(__('catalog.service.label')),
            ExportColumn::make('instructor')->label(__('scheduling.fields.instructor'))
                ->state(fn (ClassSession $record): string => $record->instructor->fullName()),
            ExportColumn::make('capacity')->label(__('scheduling.fields.capacity')),
            ExportColumn::make('status')->label(__('scheduling.fields.status'))
                ->formatStateUsing(fn (ClassSession $record): string => $record->status->label()),
            ExportColumn::make('cancel_reason')->label(__('scheduling.fields.reason')),
        ];
    }

    /**
     * @param  Builder<ClassSession>  $query
     * @return Builder<ClassSession>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['service', 'instructor']);
    }
}
