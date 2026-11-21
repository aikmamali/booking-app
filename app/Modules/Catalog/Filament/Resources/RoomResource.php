<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filament\Resources;

use App\Modules\Catalog\Actions\SaveRoom;
use App\Modules\Catalog\Filament\Resources\RoomResource\Pages\ManageRooms;
use App\Modules\Catalog\Models\Room;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return __('catalog.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('catalog.room.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog.room.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label(__('catalog.fields.name'))->required()->maxLength(100),
            TextInput::make('capacity')->label(__('catalog.fields.capacity'))->integer()->minValue(1)->maxValue(500),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('catalog.fields.name'))->searchable()->sortable(),
                TextColumn::make('capacity')->label(__('catalog.fields.capacity')),
            ])
            ->actions([
                EditAction::make()->using(fn (Room $record, array $data): Model => self::save($record, $data)),
                DeleteAction::make(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function save(?Room $room, array $data): Room
    {
        $capacity = $data['capacity'] ?? null;

        return app(SaveRoom::class)->handle($room, (string) $data['name'], $capacity === null || $capacity === '' ? null : (int) $capacity);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRooms::route('/')];
    }
}
