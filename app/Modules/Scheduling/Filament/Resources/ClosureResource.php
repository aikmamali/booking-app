<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Filament\Resources;

use App\Filament\Support\Rejections;
use App\Modules\Scheduling\Actions\DeleteClosure;
use App\Modules\Scheduling\Actions\SaveClosure;
use App\Modules\Scheduling\Filament\Resources\ClosureResource\Pages\ManageClosures;
use App\Modules\Scheduling\Models\Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final class ClosureResource extends Resource
{
    protected static ?string $model = Closure::class;

    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return __('scheduling.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('scheduling.closure.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('scheduling.closure.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            DatePicker::make('starts_on')->label(__('scheduling.fields.starts_on'))->required(),
            DatePicker::make('ends_on')->label(__('scheduling.fields.ends_on'))->required()->afterOrEqual('starts_on'),
            TextInput::make('reason')->label(__('scheduling.fields.reason'))->required()->maxLength(255)->columnSpanFull()
                ->helperText(__('scheduling.hints.closure')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_on', 'desc')
            ->columns([
                TextColumn::make('starts_on')->label(__('scheduling.fields.starts_on'))->date('d/m/Y')->sortable(),
                TextColumn::make('ends_on')->label(__('scheduling.fields.ends_on'))->date('d/m/Y'),
                TextColumn::make('reason')->label(__('scheduling.fields.reason')),
            ])
            ->actions([
                EditAction::make()->using(fn (Closure $record, array $data, EditAction $action): Model => self::save($record, $data, $action)),
                DeleteAction::make()->using(function (Closure $record): bool {
                    app(DeleteClosure::class)->handle($record);

                    return true;
                }),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function save(?Closure $closure, array $data, Action|TableAction $action): Closure
    {
        return Rejections::run(fn (): Closure => app(SaveClosure::class)->handle(
            $closure,
            substr((string) $data['starts_on'], 0, 10),
            substr((string) $data['ends_on'], 0, 10),
            (string) $data['reason'],
        ), $action);
    }

    public static function getPages(): array
    {
        return ['index' => ManageClosures::route('/')];
    }
}
