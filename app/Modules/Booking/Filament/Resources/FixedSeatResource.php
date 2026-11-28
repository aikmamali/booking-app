<?php

declare(strict_types=1);

namespace App\Modules\Booking\Filament\Resources;

use App\Filament\Support\Rejections;
use App\Modules\Booking\Actions\ApproveFixedSeat;
use App\Modules\Booking\Actions\RejectFixedSeat;
use App\Modules\Booking\Actions\RequestFixedSeat;
use App\Modules\Booking\Actions\RevokeFixedSeat;
use App\Modules\Booking\Enums\FixedSeatStatus;
use App\Modules\Booking\Filament\Resources\FixedSeatResource\Pages\ListFixedSeats;
use App\Modules\Booking\Models\FixedSeat;
use App\Modules\Booking\Models\Season;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Contracts\WeeklyClasses;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * FS-02 to FS-04: requests to approve or reject, and active seats to revoke.
 */
final class FixedSeatResource extends Resource
{
    protected static ?string $model = FixedSeat::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    protected static ?int $navigationSort = 41;

    public static function getNavigationGroup(): string
    {
        return __('booking.admin.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('booking.fixed_seats.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('booking.fixed_seats.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $requests = FixedSeat::query()->where('status', FixedSeatStatus::Requested)->count();

        return $requests > 0 ? (string) $requests : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'season']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.last_name')->label(__('booking.fields.customer'))
                    ->formatStateUsing(fn (FixedSeat $record): string => $record->user->fullName())
                    ->searchable(['first_name', 'last_name', 'email']),
                TextColumn::make('schedule_id')->label(__('booking.fixed_seats.class'))
                    ->formatStateUsing(fn (FixedSeat $record): string => app(WeeklyClasses::class)->find($record->schedule_id)?->label() ?? '—'),
                TextColumn::make('season.name')->label(__('booking.fixed_seats.season')),
                TextColumn::make('status')->label(__('booking.fixed_seats.status_label'))->badge()
                    ->formatStateUsing(fn (FixedSeatStatus $state): string => $state->label())
                    ->color(fn (FixedSeatStatus $state): string => match ($state) {
                        FixedSeatStatus::Active => 'success',
                        FixedSeatStatus::Requested => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('consecutive_cancellations')->label(__('booking.fixed_seats.cancellations_in_row')),
                TextColumn::make('revoked_reason')->label(__('booking.fixed_seats.reason'))->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('booking.fixed_seats.status_label'))->options(FixedSeatStatus::options())->multiple()
                    ->default([FixedSeatStatus::Requested->value, FixedSeatStatus::Active->value]),
                SelectFilter::make('season_id')->label(__('booking.fixed_seats.season'))->relationship('season', 'name'),
            ])
            ->actions([self::approveAction(), self::rejectAction(), self::revokeAction()]);
    }

    private static function approveAction(): TableAction
    {
        return TableAction::make('approve')->label(__('booking.fixed_seats.approve'))->icon('heroicon-o-check')->color('success')
            ->visible(fn (FixedSeat $record): bool => $record->status === FixedSeatStatus::Requested)
            ->authorize(fn (FixedSeat $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->requiresConfirmation()->modalDescription(__('booking.fixed_seats.approve_hint'))
            ->action(function (FixedSeat $record, TableAction $action): void {
                Rejections::run(fn (): FixedSeat => app(ApproveFixedSeat::class)->handle($record, (int) auth()->id()), $action);
                Notification::make()->success()->title(__('booking.fixed_seats.approved'))->send();
            });
    }

    private static function rejectAction(): TableAction
    {
        return TableAction::make('reject')->label(__('booking.fixed_seats.reject'))->icon('heroicon-o-x-mark')->color('danger')
            ->visible(fn (FixedSeat $record): bool => $record->status === FixedSeatStatus::Requested)
            ->authorize(fn (FixedSeat $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->form([TextInput::make('reason')->label(__('booking.fixed_seats.reason'))->required()->maxLength(255)])
            ->action(function (FixedSeat $record, array $data, TableAction $action): void {
                Rejections::run(fn (): FixedSeat => app(RejectFixedSeat::class)->handle($record, (string) $data['reason'], (int) auth()->id()), $action);
                Notification::make()->success()->title(__('booking.fixed_seats.rejected'))->send();
            });
    }

    private static function revokeAction(): TableAction
    {
        return TableAction::make('revoke')->label(__('booking.fixed_seats.revoke'))->icon('heroicon-o-no-symbol')->color('danger')
            ->visible(fn (FixedSeat $record): bool => $record->status === FixedSeatStatus::Active)
            ->authorize(fn (FixedSeat $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->modalDescription(__('booking.fixed_seats.revoke_hint'))
            ->form([TextInput::make('reason')->label(__('booking.fixed_seats.reason'))->required()->maxLength(255)])
            ->action(function (FixedSeat $record, array $data, TableAction $action): void {
                Rejections::run(fn (): FixedSeat => app(RevokeFixedSeat::class)->handle($record, (string) $data['reason'], (int) auth()->id()), $action);
                Notification::make()->success()->title(__('booking.fixed_seats.revoked'))->send();
            });
    }

    /**
     * Staff give a customer a seat directly: a request that is approved at once.
     */
    public static function assignAction(): Action
    {
        return Action::make('assign')->label(__('booking.fixed_seats.assign'))->icon('heroicon-o-plus')
            ->authorize(fn (): bool => auth()->user()?->can('create', FixedSeat::class) ?? false)
            ->form([
                Select::make('user_id')->label(__('booking.fields.customer'))->required()->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => BookingResource::customers($search))
                    ->getOptionLabelUsing(fn (mixed $value): ?string => User::query()->whereKey($value)->first()?->fullName()),
                Select::make('season_id')->label(__('booking.fixed_seats.season'))->required()->live()
                    ->options(fn (): array => Season::query()->orderByDesc('starts_on')->pluck('name', 'id')->all()),
                Select::make('schedule_id')->label(__('booking.fixed_seats.class'))->required()
                    ->options(fn (Get $get): array => self::classOptions($get('season_id'))),
            ])
            ->action(function (array $data, Action $action): void {
                Rejections::run(function () use ($data): FixedSeat {
                    $seat = app(RequestFixedSeat::class)->handle((int) $data['user_id'], (int) $data['schedule_id'], (int) $data['season_id']);

                    return app(ApproveFixedSeat::class)->handle($seat, (int) auth()->id());
                }, $action);
                Notification::make()->success()->title(__('booking.fixed_seats.approved'))->send();
            });
    }

    /**
     * @return array<int, string>
     */
    private static function classOptions(mixed $seasonId): array
    {
        $season = filled($seasonId) ? Season::query()->find((int) $seasonId) : null;

        if (! $season instanceof Season) {
            return [];
        }

        $options = [];

        foreach (app(WeeklyClasses::class)->runningBetween($season->starts_on->toDateString(), $season->ends_on->toDateString()) as $class) {
            $options[$class->id] = $class->label();
        }

        return $options;
    }

    public static function getPages(): array
    {
        return ['index' => ListFixedSeats::route('/')];
    }
}
