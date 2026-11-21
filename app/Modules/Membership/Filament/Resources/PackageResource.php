<?php

declare(strict_types=1);

namespace App\Modules\Membership\Filament\Resources;

use App\Filament\Support\Rejections;
use App\Filament\Support\Translatable;
use App\Modules\Catalog\Models\Service;
use App\Modules\Membership\Actions\SavePackage;
use App\Modules\Membership\Data\PackageData;
use App\Modules\Membership\Filament\Resources\PackageResource\Pages\ManagePackages;
use App\Modules\Membership\Models\Package;
use App\Shared\Credits;
use App\Shared\Money;
use App\View\Format;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return __('membership.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('membership.package.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('membership.package.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            ...Translatable::text('name', __('membership.fields.name')),
            TextInput::make('credits')->label(__('membership.fields.credits'))->numeric()->required()
                ->minValue(0.5)->maxValue(999)->step(0.5)->rule('multiple_of:0.5'),
            TextInput::make('validity_days')->label(__('membership.fields.validity_days'))->integer()->required()
                ->minValue(1)->maxValue(730)->suffix(__('membership.units.days')),
            TextInput::make('price')->label(__('membership.fields.price'))->numeric()->required()
                ->minValue(0)->maxValue(100000)->step(0.01)->suffix('€'),
            TextInput::make('vat_rate')->label(__('membership.fields.vat_rate'))->integer()->required()
                ->minValue(0)->maxValue(100)->default(24)->suffix('%'),
            CheckboxList::make('service_ids')->label(__('membership.fields.services'))->required()->columns(2)
                ->options(fn (): array => Service::query()->orderBy('position')->get()->pluck('name', 'id')->all())
                ->columnSpanFull(),
            Toggle::make('is_purchasable_online')->label(__('membership.fields.is_purchasable_online')),
            Toggle::make('is_active')->label(__('membership.fields.is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('position')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')->label(__('membership.fields.name'))->searchable(),
                TextColumn::make('credits')->label(__('membership.fields.credits'))
                    ->formatStateUsing(fn (Credits $state): string => Format::credits($state)),
                TextColumn::make('validity_days')->label(__('membership.fields.validity_days'))
                    ->suffix(' '.__('membership.units.days')),
                TextColumn::make('price_cents')->label(__('membership.fields.price'))
                    ->formatStateUsing(fn (int $state): string => Format::money(Money::ofCents($state))),
                IconColumn::make('is_purchasable_online')->label(__('membership.fields.is_purchasable_online'))->boolean(),
                IconColumn::make('is_active')->label(__('membership.fields.is_active'))->boolean(),
            ])
            ->actions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, Package $record): array => self::toFormState($record))
                    ->using(fn (Package $record, array $data, EditAction $action): Model => self::save($record, $data, $action)),
                DeleteAction::make(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function save(?Package $package, array $data, Action|TableAction $action): Package
    {
        return Rejections::run(fn (): Package => app(SavePackage::class)->handle($package, new PackageData(
            name: (array) $data['name'],
            credits: Credits::of((string) $data['credits']),
            validity_days: (int) $data['validity_days'],
            price_cents: Money::fromDecimal((string) $data['price'])->cents,
            vat_rate: (int) $data['vat_rate'],
            is_purchasable_online: (bool) ($data['is_purchasable_online'] ?? false),
            is_active: (bool) ($data['is_active'] ?? true),
            service_ids: array_values(array_map(intval(...), (array) ($data['service_ids'] ?? []))),
        )), $action);
    }

    /**
     * @return array<string, mixed>
     */
    public static function toFormState(Package $package): array
    {
        return [
            'name' => $package->getTranslations('name'),
            'credits' => $package->credits->toDecimal(),
            'validity_days' => $package->validity_days,
            'price' => $package->price()->toDecimal(),
            'vat_rate' => $package->vat_rate,
            'service_ids' => $package->services()->pluck('services.id')->all(),
            'is_purchasable_online' => $package->is_purchasable_online,
            'is_active' => $package->is_active,
        ];
    }

    public static function getPages(): array
    {
        return ['index' => ManagePackages::route('/')];
    }
}
