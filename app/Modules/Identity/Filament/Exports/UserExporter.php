<?php

declare(strict_types=1);

namespace App\Modules\Identity\Filament\Exports;

use App\Filament\Support\BaseExporter;
use App\Modules\Identity\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Builder;

final class UserExporter extends BaseExporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('first_name')->label(__('identity.fields.first_name')),
            ExportColumn::make('last_name')->label(__('identity.fields.last_name')),
            ExportColumn::make('email')->label(__('identity.fields.email')),
            ExportColumn::make('phone')->label(__('identity.fields.phone')),
            ExportColumn::make('role')->label(__('identity.fields.role'))
                ->state(fn (User $record): string => $record->primaryRole()?->label() ?? ''),
            ExportColumn::make('created_at')->label(__('identity.fields.created_at')),
            ExportColumn::make('last_login_at')->label(__('identity.fields.last_login_at')),
            ExportColumn::make('marketing_consent_at')->label(__('identity.fields.marketing_consent')),
        ];
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with('roles')->whereNull('anonymized_at');
    }
}
