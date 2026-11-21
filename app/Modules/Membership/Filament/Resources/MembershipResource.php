<?php

declare(strict_types=1);

namespace App\Modules\Membership\Filament\Resources;

use App\Filament\Support\Rejections;
use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Actions\AdjustCredits;
use App\Modules\Membership\Actions\SellPackage;
use App\Modules\Membership\Data\SaleData;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Filament\Resources\MembershipResource\Pages\ListMemberships;
use App\Modules\Membership\Filament\Resources\MembershipResource\Pages\ViewMembership;
use App\Modules\Membership\Filament\Resources\MembershipResource\RelationManagers\TransactionsRelationManager;
use App\Modules\Membership\Models\CreditTransaction;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\Package;
use App\Shared\BusinessTime;
use App\Shared\Credits;
use App\View\Format;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class MembershipResource extends Resource
{
    protected static ?string $model = Membership::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return __('membership.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('membership.membership.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('membership.membership.plural');
    }

    /**
     * @return Builder<Membership>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user')
            ->addSelect(['balance' => CreditTransaction::query()
                ->select('balance_after')
                ->whereColumn('membership_id', 'memberships.id')
                ->orderByDesc('id')
                ->limit(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ends_on')
            ->columns([
                TextColumn::make('user.last_name')->label(__('membership.fields.customer'))->searchable(['first_name', 'last_name', 'email'])
                    ->formatStateUsing(fn (Membership $record): string => $record->user->fullName()),
                TextColumn::make('package_name')->label(__('membership.package.label')),
                TextColumn::make('starts_on')->label(__('membership.fields.starts_on'))->date('d/m/Y')->sortable(),
                TextColumn::make('ends_on')->label(__('membership.fields.ends_on'))->date('d/m/Y')->sortable(),
                TextColumn::make('balance')->label(__('membership.fields.balance'))
                    ->formatStateUsing(fn (?string $state): string => Format::credits(Credits::of($state ?? '0'))),
                self::statusColumn(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('membership.fields.status'))->options(MembershipStatus::options())
                    ->default(MembershipStatus::Active->value),
            ])
            ->actions([ViewAction::make(), self::adjustAction()]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make()->columns(3)->schema([
                TextEntry::make('user.email')->label(__('membership.fields.customer'))
                    ->formatStateUsing(fn (Membership $record): string => $record->user->fullName().' · '.$record->user->email),
                TextEntry::make('package_name')->label(__('membership.package.label')),
                TextEntry::make('status')->label(__('membership.fields.status'))->badge()
                    ->formatStateUsing(fn (MembershipStatus $state): string => $state->label()),
                TextEntry::make('starts_on')->label(__('membership.fields.starts_on'))->date('d/m/Y'),
                TextEntry::make('ends_on')->label(__('membership.fields.ends_on'))->date('d/m/Y'),
                TextEntry::make('balance')->label(__('membership.fields.balance'))
                    ->formatStateUsing(fn (?string $state): string => Format::credits(Credits::of($state ?? '0'))),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [TransactionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMemberships::route('/'),
            'view' => ViewMembership::route('/{record}'),
        ];
    }

    public static function sellAction(): Action
    {
        return Action::make('sell')->label(__('membership.actions.sell'))->icon('heroicon-o-shopping-cart')
            ->authorize(fn (): bool => auth()->user()?->can('sell', Package::class) ?? false)
            ->form([
                Select::make('user_id')->label(__('membership.fields.customer'))->required()->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => self::customerOptions($search))
                    ->getOptionLabelUsing(fn (mixed $value): ?string => User::query()->whereKey($value)->first()?->fullName()),
                Select::make('package_id')->label(__('membership.package.label'))->required()
                    ->options(fn (): array => Package::query()->where('is_active', true)->orderBy('position')->get()->pluck('name', 'id')->all()),
                DatePicker::make('starts_on')->label(__('membership.fields.starts_on'))->required()
                    ->default(fn (): string => app(BusinessTime::class)->today()),
                Select::make('payment_method')->label(__('membership.fields.payment_method'))->required()
                    ->options(PaymentMethod::deskOptions())->default(PaymentMethod::Cash->value),
            ])
            ->action(function (array $data, Action $action): void {
                Rejections::run(fn (): Membership => app(SellPackage::class)->handle(new SaleData(
                    user_id: (int) $data['user_id'],
                    package_id: (int) $data['package_id'],
                    starts_on: substr((string) $data['starts_on'], 0, 10),
                    payment_method: PaymentMethod::from((string) $data['payment_method']),
                ), auth()->id() === null ? null : (int) auth()->id()), $action);

                Notification::make()->success()->title(__('membership.notices.sold'))->send();
            });
    }

    public static function adjustAction(): TableAction
    {
        return TableAction::make('adjust')->label(__('membership.actions.adjust'))->icon('heroicon-o-adjustments-horizontal')
            ->authorize(fn (Membership $record): bool => auth()->user()?->can('adjustCredits', $record) ?? false)
            ->form([
                TextInput::make('amount')->label(__('membership.fields.adjustment'))->numeric()->required()
                    ->step(0.5)->rule('multiple_of:0.5')->notIn(['0', '0.0', '0.00'])->minValue(-999)->maxValue(999)
                    ->helperText(__('membership.hints.adjustment')),
                TextInput::make('reason')->label(__('membership.fields.reason'))->required()->maxLength(255),
            ])
            ->action(function (Membership $record, array $data, TableAction $action): void {
                Rejections::run(fn (): CreditTransaction => app(AdjustCredits::class)->handle(
                    $record,
                    Credits::of((string) $data['amount']),
                    (string) $data['reason'],
                    auth()->id() === null ? null : (int) auth()->id(),
                ), $action);

                Notification::make()->success()->title(__('membership.notices.adjusted'))->send();
            });
    }

    /**
     * @return array<int, string>
     */
    private static function customerOptions(string $search): array
    {
        return User::role(Role::Customer->value)
            ->whereNull('anonymized_at')
            ->where(fn (Builder $q) => $q->where('last_name', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"))
            ->orderBy('last_name')->limit(20)->get()
            ->mapWithKeys(fn (User $u): array => [$u->id => $u->fullName().' · '.$u->email])
            ->all();
    }

    private static function statusColumn(): TextColumn
    {
        return TextColumn::make('status')->label(__('membership.fields.status'))->badge()
            ->formatStateUsing(fn (MembershipStatus $state): string => $state->label())
            ->color(fn (MembershipStatus $state): string => match ($state) {
                MembershipStatus::Active => 'success',
                MembershipStatus::Pending, MembershipStatus::Frozen => 'warning',
                MembershipStatus::Expired, MembershipStatus::Cancelled => 'gray',
            });
    }
}
