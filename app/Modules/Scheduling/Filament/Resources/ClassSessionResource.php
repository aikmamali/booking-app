<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Filament\Resources;

use App\Filament\Support\Rejections;
use App\Modules\Scheduling\Actions\CancelSession;
use App\Modules\Scheduling\Actions\ChangeSession;
use App\Modules\Scheduling\Data\SessionChangeData;
use App\Modules\Scheduling\Enums\SessionStatus;
use App\Modules\Scheduling\Filament\Resources\ClassSessionResource\Pages\ListClassSessions;
use App\Modules\Scheduling\Models\ClassSession;
use App\Modules\Scheduling\Support\BusinessTime;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ClassSessionResource extends Resource
{
    protected static ?string $model = ClassSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return __('scheduling.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('scheduling.session.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('scheduling.session.plural');
    }

    public static function table(Table $table): Table
    {
        $tz = app(BusinessTime::class)->timezone();

        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['service', 'instructor', 'room']))
            ->defaultSort('starts_at')
            ->columns([
                TextColumn::make('starts_at')->label(__('scheduling.fields.starts_at'))
                    ->dateTime('D d/m/Y H:i', timezone: $tz)->sortable(),
                TextColumn::make('service.name')->label(__('catalog.service.label')),
                TextColumn::make('instructor_id')->label(__('scheduling.fields.instructor'))
                    ->formatStateUsing(fn (ClassSession $record): string => $record->instructor->fullName()),
                TextColumn::make('capacity')->label(__('scheduling.fields.capacity')),
                TextColumn::make('room.name')->label(__('catalog.room.label'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->label(__('scheduling.fields.status'))->badge()
                    ->formatStateUsing(fn (SessionStatus $state): string => $state->label())
                    ->color(fn (SessionStatus $state): string => $state === SessionStatus::Cancelled ? 'danger' : 'success')
                    ->description(fn (ClassSession $record): ?string => $record->cancel_reason),
            ])
            ->filters(self::filters())
            ->actions([self::changeAction($tz), self::cancelAction()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListClassSessions::route('/')];
    }

    /**
     * @return list<BaseFilter>
     */
    private static function filters(): array
    {
        return [
            Filter::make('upcoming')->label(__('scheduling.filters.upcoming'))->default()
                ->query(fn (Builder $query): Builder => $query->whereDate('starts_at', '>=', now()->toDateString())),
            SelectFilter::make('status')->label(__('scheduling.fields.status'))
                ->options([SessionStatus::Scheduled->value => SessionStatus::Scheduled->label(), SessionStatus::Cancelled->value => SessionStatus::Cancelled->label()]),
            SelectFilter::make('service_id')->label(__('catalog.service.label'))->relationship('service', 'name'),
            SelectFilter::make('instructor_id')->label(__('scheduling.fields.instructor'))
                ->options(fn (): array => ScheduleResource::instructorOptions()),
        ];
    }

    private static function changeAction(string $tz): Action
    {
        return Action::make('change')->label(__('scheduling.actions.change'))->icon('heroicon-o-pencil-square')
            ->visible(fn (ClassSession $record): bool => ! $record->isCancelled() && $record->starts_at->isFuture())
            ->authorize(fn (ClassSession $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->modalSubmitActionLabel(__('scheduling.actions.save'))
            ->fillForm(fn (ClassSession $record): array => [
                'instructor_id' => $record->instructor_id,
                'room_id' => $record->room_id,
                'capacity' => $record->capacity,
                'date' => $record->starts_at->setTimezone($tz)->format('Y-m-d'),
                'start_time' => $record->starts_at->setTimezone($tz)->format('H:i'),
                'lock_version' => $record->lock_version,
            ])
            ->form([
                Select::make('instructor_id')->label(__('scheduling.fields.instructor'))
                    ->options(fn (): array => ScheduleResource::instructorOptions())->required(),
                Select::make('room_id')->label(__('catalog.room.label'))->relationship('room', 'name'),
                TextInput::make('capacity')->label(__('scheduling.fields.capacity'))->integer()->minValue(1)->maxValue(500)->required(),
                DatePicker::make('date')->label(__('scheduling.fields.date'))->required(),
                TimePicker::make('start_time')->label(__('scheduling.fields.start_time'))->seconds(false)->required(),
                Hidden::make('lock_version'),
            ])
            ->action(function (ClassSession $record, array $data, Action $action): void {
                Rejections::run(fn (): ClassSession => app(ChangeSession::class)->handle($record, new SessionChangeData(
                    instructor_id: (int) $data['instructor_id'],
                    room_id: ($data['room_id'] ?? null) === null ? null : (int) $data['room_id'],
                    capacity: (int) $data['capacity'],
                    date: substr((string) $data['date'], 0, 10),
                    start_time: substr((string) $data['start_time'], 0, 5),
                    lock_version: (int) $data['lock_version'],
                )), $action);

                Notification::make()->success()->title(__('scheduling.notices.changed'))->send();
            });
    }

    private static function cancelAction(): Action
    {
        return Action::make('cancel')->label(__('scheduling.actions.cancel'))->icon('heroicon-o-x-circle')->color('danger')
            ->visible(fn (ClassSession $record): bool => ! $record->isCancelled() && $record->starts_at->isFuture())
            ->authorize(fn (ClassSession $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->requiresConfirmation()
            ->modalDescription(__('scheduling.hints.cancel'))
            ->modalSubmitActionLabel(__('scheduling.actions.confirm_cancel'))
            ->form([
                TextInput::make('reason')->label(__('scheduling.fields.reason'))->required()->maxLength(255),
            ])
            ->action(function (ClassSession $record, array $data, Action $action): void {
                Rejections::run(fn () => app(CancelSession::class)->handle($record, (string) $data['reason']), $action);

                Notification::make()->success()->title(__('scheduling.notices.cancelled'))->send();
            });
    }
}
