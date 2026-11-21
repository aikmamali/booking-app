<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filament\Resources;

use App\Filament\Support\Translatable;
use App\Modules\Catalog\Actions\SaveServiceCategory;
use App\Modules\Catalog\Filament\Resources\ServiceCategoryResource\Pages\ManageServiceCategories;
use App\Modules\Catalog\Models\ServiceCategory;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final class ServiceCategoryResource extends Resource
{
    protected static ?string $model = ServiceCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return __('catalog.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('catalog.category.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog.category.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema(Translatable::text('name', __('catalog.fields.name')));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('position')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')->label(__('catalog.fields.name'))->searchable(),
                TextColumn::make('services_count')->counts('services')->label(__('catalog.service.plural')),
            ])
            ->actions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, ServiceCategory $record): array => [
                        ...$data, 'name' => $record->getTranslations('name'),
                    ])
                    ->using(fn (ServiceCategory $record, array $data): Model => app(SaveServiceCategory::class)->handle($record, $data['name'])),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageServiceCategories::route('/')];
    }
}
