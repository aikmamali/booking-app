<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Filament\Resources;

use App\Filament\Support\Rejections;
use App\Modules\Catalog\Models\Service;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Actions\SaveSchedule;
use App\Modules\Scheduling\Data\ScheduleData;
use App\Modules\Scheduling\Enums\Weekday;
use App\Modules\Scheduling\Filament\Resources\ScheduleResource\Pages\ManageSchedules;
use App\Modules\Scheduling\Models\Schedule;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return __('scheduling.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('scheduling.schedule.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('scheduling.schedule.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Select::make('service_id')->label(__('catalog.service.label'))
                ->options(fn (): array => Service::query()->where('is_active', true)->orderBy('position')->get()->pluck('name', 'id')->all())
                ->required()->live()
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    $service = $state === null ? null : Service::query()->find($state);
                    if ($service !== null) {
                        $set('capacity', $service->default_capacity);
                    }
                }),
            Select::make('instructor_id')->label(__('scheduling.fields.instructor'))
                ->options(fn (): array => self::instructorOptions())->required(),
            Select::make('weekday')->label(__('scheduling.fields.weekday'))->options(Weekday::options())->required()
                ->disabledOn('edit')->helperText(fn (string $operation): ?string => $operation === 'edit' ? (string) __('scheduling.hints.slot_is_fixed') : null),
            TimePicker::make('start_time')->label(__('scheduling.fields.start_time'))->seconds(false)->required()
                ->disabledOn('edit'),
            TextInput::make('capacity')->label(__('scheduling.fields.capacity'))->integer()->minValue(1)->maxValue(500)->required(),
            Select::make('room_id')->label(__('catalog.room.label'))->relationship('room', 'name'),
            DatePicker::make('valid_from')->label(__('scheduling.fields.valid_from'))->required()->default(now()),
            DatePicker::make('valid_until')->label(__('scheduling.fields.valid_until'))->afterOrEqual('valid_from'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['service', 'instructor', 'room']))
            ->defaultSort(fn (Builder $query): Builder => $query->orderBy('weekday')->orderBy('start_time'))
            ->columns([
                TextColumn::make('weekday')->label(__('scheduling.fields.weekday'))
                    ->formatStateUsing(fn (Weekday $state): string => $state->label()),
                TextColumn::make('start_time')->label(__('scheduling.fields.start_time'))
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 5)),
                TextColumn::make('service.name')->label(__('catalog.service.label')),
                TextColumn::make('instructor.first_name')->label(__('scheduling.fields.instructor'))
                    ->formatStateUsing(fn (Schedule $record): string => $record->instructor->fullName()),
                TextColumn::make('capacity')->label(__('scheduling.fields.capacity')),
                TextColumn::make('room.name')->label(__('catalog.room.label'))->toggleable(),
                TextColumn::make('valid_from')->label(__('scheduling.fields.valid_from'))->date('d/m/Y'),
                TextColumn::make('valid_until')->label(__('scheduling.fields.valid_until'))->date('d/m/Y')
                    ->placeholder(__('scheduling.hints.open_ended')),
            ])
            ->filters([
                SelectFilter::make('weekday')->label(__('scheduling.fields.weekday'))->options(Weekday::options()),
                SelectFilter::make('service_id')->label(__('catalog.service.label'))->relationship('service', 'name'),
            ])
            ->actions([
                EditAction::make()->using(fn (Schedule $record, array $data, EditAction $action): Model => Rejections::run(
                    fn (): Schedule => app(SaveSchedule::class)->handle($record, self::toData([
                        ...$data,
                        'weekday' => $record->weekday->value,
                        'start_time' => $record->start_time,
                    ])),
                    $action,
                )),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function toData(array $data): ScheduleData
    {
        $room = $data['room_id'] ?? null;
        $until = $data['valid_until'] ?? null;

        return new ScheduleData(
            service_id: (int) $data['service_id'],
            instructor_id: (int) $data['instructor_id'],
            room_id: $room === null || $room === '' ? null : (int) $room,
            weekday: Weekday::from((int) $data['weekday']),
            start_time: substr((string) $data['start_time'], 0, 5),
            capacity: (int) $data['capacity'],
            valid_from: substr((string) $data['valid_from'], 0, 10),
            valid_until: $until === null || $until === '' ? null : substr((string) $until, 0, 10),
        );
    }

    /**
     * @return array<int, string>
     */
    public static function instructorOptions(): array
    {
        return User::role(Role::Instructor->value)
            ->whereNull('anonymized_at')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => $user->fullName()])
            ->all();
    }

    public static function getPages(): array
    {
        return ['index' => ManageSchedules::route('/')];
    }
}
