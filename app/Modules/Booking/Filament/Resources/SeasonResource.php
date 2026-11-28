<?php

declare(strict_types=1);

namespace App\Modules\Booking\Filament\Resources;

use App\Filament\Support\Rejections;
use App\Modules\Booking\Actions\SaveSeason;
use App\Modules\Booking\Filament\Resources\SeasonResource\Pages\ManageSeasons;
use App\Modules\Booking\Models\Season;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * FS-01: seasons (e.g. 31/8/2026 – 30/6/2027).
 */
final class SeasonResource extends Resource
{
    protected static ?string $model = Season::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?int $navigationSort = 40;

    public static function getNavigationGroup(): string
    {
        return __('booking.admin.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('booking.seasons.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('booking.seasons.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            TextInput::make('name')->label(__('booking.seasons.name'))->required()->maxLength(100),
            DatePicker::make('starts_on')->label(__('booking.seasons.starts_on'))->required(),
            DatePicker::make('ends_on')->label(__('booking.seasons.ends_on'))->required()->after('starts_on'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('starts_on', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('booking.seasons.name')),
                TextColumn::make('starts_on')->label(__('booking.seasons.starts_on'))->date('d/m/Y'),
                TextColumn::make('ends_on')->label(__('booking.seasons.ends_on'))->date('d/m/Y'),
            ])
            ->actions([
                EditAction::make()->using(fn (Season $record, array $data, EditAction $action): Season => self::save($data, $action, $record)),
            ]);
    }

    public static function createAction(): CreateAction
    {
        return CreateAction::make()->using(fn (array $data, CreateAction $action): Season => self::save($data, $action));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function save(array $data, CreateAction|EditAction $action, ?Season $record = null): Season
    {
        return Rejections::run(fn (): Season => app(SaveSeason::class)->handle(
            (string) $data['name'],
            substr((string) $data['starts_on'], 0, 10),
            substr((string) $data['ends_on'], 0, 10),
            $record,
        ), $action);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSeasons::route('/')];
    }
}
