<?php

declare(strict_types=1);

namespace App\Modules\Membership\Filament\Resources\MembershipResource\RelationManagers;

use App\Modules\Membership\Enums\TransactionType;
use App\Modules\Membership\Models\CreditTransaction;
use App\Shared\BusinessTime;
use App\Shared\Credits;
use App\View\Format;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only ledger history (append-only: no create, edit or delete here).
 */
final class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('membership.ledger.title');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('actor'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('membership.ledger.when'))
                    ->dateTime('d/m/Y H:i', timezone: app(BusinessTime::class)->timezone()),
                TextColumn::make('type')->label(__('membership.ledger.type'))->badge()
                    ->formatStateUsing(fn (TransactionType $state): string => $state->label()),
                TextColumn::make('amount')->label(__('membership.ledger.amount'))
                    ->formatStateUsing(fn (Credits $state): string => ($state->isPositive() ? '+' : '').Format::credits($state)),
                TextColumn::make('balance_after')->label(__('membership.ledger.balance_after'))
                    ->formatStateUsing(fn (Credits $state): string => Format::credits($state)),
                TextColumn::make('reason')->label(__('membership.fields.reason'))->placeholder('—'),
                TextColumn::make('actor_id')->label(__('membership.ledger.actor'))
                    ->formatStateUsing(fn (CreditTransaction $record): string => $record->actor?->fullName() ?? '—'),
            ]);
    }
}
