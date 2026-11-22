<?php

declare(strict_types=1);

namespace App\Modules\Membership\Filament\Exports;

use App\Filament\Support\BaseExporter;
use App\Modules\Membership\Contracts\CreditLedger;
use App\Modules\Membership\Models\Membership;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Builder;

final class MembershipExporter extends BaseExporter
{
    protected static ?string $model = Membership::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('customer')->label(__('membership.fields.customer'))
                ->state(fn (Membership $record): string => $record->user->fullName()),
            ExportColumn::make('user.email')->label(__('identity.fields.email')),
            ExportColumn::make('package_name')->label(__('membership.package.label')),
            ExportColumn::make('starts_on')->label(__('membership.fields.starts_on')),
            ExportColumn::make('ends_on')->label(__('membership.fields.ends_on')),
            ExportColumn::make('status')->label(__('membership.fields.status'))
                ->formatStateUsing(fn (Membership $record): string => $record->status->label()),
            ExportColumn::make('credits')->label(__('membership.fields.credits'))
                ->state(fn (Membership $record): string => $record->credits->toDecimal()),
            ExportColumn::make('balance')->label(__('membership.fields.balance'))
                ->state(fn (Membership $record): string => app(CreditLedger::class)->balance($record->id)->toDecimal()),
            ExportColumn::make('price')->label(__('membership.fields.price'))
                ->state(fn (Membership $record): string => number_format($record->price_cents / 100, 2, '.', '')),
        ];
    }

    /**
     * @param  Builder<Membership>  $query
     * @return Builder<Membership>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with('user');
    }
}
