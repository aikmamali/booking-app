<?php

declare(strict_types=1);

namespace App\Modules\Identity\Filament\Resources;

use App\Modules\Identity\Actions\DeleteAccount;
use App\Modules\Identity\Actions\RegenerateCheckinToken;
use App\Modules\Identity\Actions\ResetTwoFactor;
use App\Modules\Identity\Actions\SendInvitation;
use App\Modules\Identity\Enums\Locale;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Filament\Resources\UserResource\Pages\CreateUser;
use App\Modules\Identity\Filament\Resources\UserResource\Pages\EditUser;
use App\Modules\Identity\Filament\Resources\UserResource\Pages\ListUsers;
use App\Modules\Identity\Models\User;
use App\Shared\Validation\E164Phone;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $recordTitleAttribute = 'last_name';

    public static function getNavigationGroup(): string
    {
        return __('identity.admin.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('identity.admin.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('identity.admin.users');
    }

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('roles');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->columns(2)->schema([
                TextInput::make('first_name')->label(__('identity.fields.first_name'))->required()->maxLength(100),
                TextInput::make('last_name')->label(__('identity.fields.last_name'))->required()->maxLength(100),
                TextInput::make('email')->label(__('identity.fields.email'))->email()->required()->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')->label(__('identity.fields.phone'))->tel()
                    ->helperText(__('identity.fields.phone_hint'))
                    ->requiredIf('role', Role::Customer->value)
                    ->rule(new E164Phone),
                TextInput::make('phone_alt')->label(__('identity.fields.phone_alt'))->tel()->rule(new E164Phone)
                    ->hiddenOn('create'),
                TextInput::make('vat_number')->label(__('identity.fields.vat_number'))->regex('/^\d{9}$/')
                    ->hiddenOn('create'),
                Select::make('locale')->label(__('identity.fields.locale'))->options(Locale::options())
                    ->default(Locale::Greek->value)->required(),
                Select::make('role')->label(__('identity.fields.role'))->options(Role::options())
                    ->default(Role::Customer->value)->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('anonymized_at'))
            ->defaultSort('last_name')
            ->columns(self::columns())
            ->filters([
                SelectFilter::make('role')->label(__('identity.fields.role'))->options(Role::options())
                    ->relationship('roles', 'name'),
                TernaryFilter::make('email_verified_at')->label(__('identity.fields.email_verified'))->nullable(),
            ])
            ->actions([
                EditAction::make(),
                ActionGroup::make(self::recordActions()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * @return list<Column>
     */
    private static function columns(): array
    {
        return [
            TextColumn::make('last_name')->label(__('identity.fields.last_name'))->searchable()->sortable(),
            TextColumn::make('first_name')->label(__('identity.fields.first_name'))->searchable()->sortable(),
            TextColumn::make('email')->label(__('identity.fields.email'))->searchable(),
            TextColumn::make('phone')->label(__('identity.fields.phone'))->searchable(),
            TextColumn::make('roles.name')->label(__('identity.fields.role'))->badge()
                ->formatStateUsing(fn (string $state): string => Role::tryFrom($state)?->label() ?? $state),
            IconColumn::make('email_verified_at')->label(__('identity.fields.email_verified'))->boolean()
                ->getStateUsing(fn (User $record): bool => $record->email_verified_at !== null),
            TextColumn::make('last_login_at')->label(__('identity.fields.last_login_at'))
                ->dateTime('d/m/Y H:i', timezone: 'Europe/Athens')->sortable()->toggleable(),
        ];
    }

    /**
     * @return list<Action>
     */
    private static function recordActions(): array
    {
        return [
            Action::make('resendInvitation')->label(__('identity.admin.resend_invitation'))
                ->icon('heroicon-o-envelope')
                ->visible(fn (User $record): bool => $record->last_login_at === null)
                ->authorize(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                ->action(function (User $record, SendInvitation $send): void {
                    $send->handle($record);
                    Notification::make()->success()->title(__('identity.admin.invitation_sent'))->send();
                }),
            Action::make('regenerateCheckin')->label(__('identity.admin.regenerate_checkin'))
                ->icon('heroicon-o-qr-code')->requiresConfirmation()
                ->authorize(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                ->action(function (User $record, RegenerateCheckinToken $regenerate): void {
                    $regenerate->handle($record);
                    Notification::make()->success()->title(__('identity.admin.checkin_regenerated'))->send();
                }),
            Action::make('resetTwoFactor')->label(__('identity.two_factor.reset'))
                ->icon('heroicon-o-shield-exclamation')->color('warning')->requiresConfirmation()
                ->modalDescription(__('identity.two_factor.reset_confirm'))
                ->visible(fn (User $record): bool => $record->two_factor_secret !== null && ! $record->is(auth()->user()))
                ->authorize(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                ->action(function (User $record, ResetTwoFactor $reset): void {
                    $actor = auth()->user();
                    abort_unless($actor instanceof User, 403);
                    $reset->handle($record, $actor);
                    Notification::make()->success()->title(__('identity.two_factor.reset_done'))->send();
                }),
            Action::make('anonymize')->label(__('identity.admin.anonymize'))
                ->icon('heroicon-o-trash')->color('danger')->requiresConfirmation()
                ->modalDescription(__('identity.admin.anonymize_confirm'))
                // Admins pass every policy check via the super-admin gate, so guard self-deletion here.
                ->hidden(fn (User $record): bool => $record->is(auth()->user()))
                ->authorize(fn (User $record): bool => auth()->user()?->can('delete', $record) ?? false)
                ->action(function (User $record, DeleteAccount $delete): void {
                    $delete->handle($record);
                    Notification::make()->success()->title(__('identity.admin.anonymized'))->send();
                }),
        ];
    }
}
