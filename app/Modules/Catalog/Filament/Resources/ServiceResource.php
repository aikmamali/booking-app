<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Filament\Resources;

use App\Filament\Support\Translatable;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Catalog\Data\PolicyOverrides;
use App\Modules\Catalog\Data\ServiceData;
use App\Modules\Catalog\Filament\Resources\ServiceResource\Pages\CreateService;
use App\Modules\Catalog\Filament\Resources\ServiceResource\Pages\EditService;
use App\Modules\Catalog\Filament\Resources\ServiceResource\Pages\ListServices;
use App\Modules\Catalog\Models\Service;
use App\Shared\Credits;
use App\Shared\Money;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return __('catalog.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('catalog.service.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog.service.plural');
    }

    public static function form(Form $form): Form
    {
        $settings = app(BookingPolicySettings::class);

        return $form->schema([
            Section::make(__('catalog.sections.details'))->columns(2)->schema([
                Select::make('category_id')->label(__('catalog.category.label'))
                    ->relationship('category', 'name')->required()->preload(),
                FileUpload::make('image_path')->label(__('catalog.fields.image'))
                    ->image()->disk('public')->directory('services')->maxSize(2048),
                ...Translatable::text('name', __('catalog.fields.name')),
                ...Translatable::textarea('description', __('catalog.fields.description')),
                Toggle::make('is_active')->label(__('catalog.fields.is_active'))->default(true),
            ]),
            Section::make(__('catalog.sections.booking'))->columns(2)->schema([
                TextInput::make('duration_minutes')->label(__('catalog.fields.duration_minutes'))
                    ->integer()->required()->minValue(5)->maxValue(600)->default(60)->suffix(__('catalog.units.minutes')),
                TextInput::make('default_capacity')->label(__('catalog.fields.default_capacity'))
                    ->integer()->required()->minValue(1)->maxValue(500)->default($settings->default_capacity),
                TextInput::make('credit_cost')->label(__('catalog.fields.credit_cost'))
                    ->numeric()->required()->minValue(0.5)->maxValue(100)->step(0.5)->rule('multiple_of:0.5')
                    ->default($settings->credit_cost->toDecimal())
                    ->helperText(__('catalog.hints.credit_cost')),
                TextInput::make('drop_in_price')->label(__('catalog.fields.drop_in_price'))
                    ->numeric()->minValue(0)->maxValue(10000)->step(0.01)->suffix('€')
                    ->helperText(__('catalog.hints.drop_in_price')),
            ]),
            self::overridesSection($settings),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('position')
            ->defaultSort('position')
            ->modifyQueryUsing(fn ($query) => $query->with('category'))
            ->columns([
                TextColumn::make('name')->label(__('catalog.fields.name'))->searchable(),
                TextColumn::make('category.name')->label(__('catalog.category.label')),
                TextColumn::make('duration_minutes')->label(__('catalog.fields.duration_minutes'))
                    ->suffix(' '.__('catalog.units.minutes')),
                TextColumn::make('default_capacity')->label(__('catalog.fields.default_capacity')),
                TextColumn::make('credit_cost')->label(__('catalog.fields.credit_cost'))
                    ->formatStateUsing(fn (Credits $state): string => str_replace('.', ',', rtrim(rtrim($state->toDecimal(), '0'), '.'))),
                IconColumn::make('is_active')->label(__('catalog.fields.is_active'))->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label(__('catalog.fields.is_active')),
                TrashedFilter::make(),
            ])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }

    /**
     * Maps the form state to the action's input.
     *
     * @param  array<string, mixed>  $data
     */
    public static function toData(array $data): ServiceData
    {
        $price = $data['drop_in_price'] ?? null;

        return new ServiceData(
            category_id: (int) $data['category_id'],
            name: (array) $data['name'],
            description: (array) ($data['description'] ?? []),
            image_path: is_string($data['image_path'] ?? null) ? $data['image_path'] : null,
            duration_minutes: (int) $data['duration_minutes'],
            default_capacity: (int) $data['default_capacity'],
            credit_cost: Credits::of((string) $data['credit_cost']),
            drop_in_price_cents: $price === null || $price === '' ? null : Money::fromDecimal((string) $price)->cents,
            is_active: (bool) ($data['is_active'] ?? true),
            policy_overrides: PolicyOverrides::fromInput((array) ($data['policy_overrides'] ?? [])),
        );
    }

    /**
     * Inverse of toData() for filling the edit form.
     *
     * @return array<string, mixed>
     */
    public static function toFormState(Service $service): array
    {
        return [
            'category_id' => $service->category_id,
            'name' => $service->getTranslations('name'),
            'description' => $service->getTranslations('description'),
            'image_path' => $service->image_path,
            'duration_minutes' => $service->duration_minutes,
            'default_capacity' => $service->default_capacity,
            'credit_cost' => $service->credit_cost->toDecimal(),
            'drop_in_price' => $service->dropInPrice()?->toDecimal(),
            'is_active' => $service->is_active,
            'policy_overrides' => array_map(
                fn (mixed $v): mixed => is_bool($v) ? ($v ? '1' : '0') : $v,
                $service->policy_overrides?->toArray() ?? [],
            ),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }

    private static function overridesSection(BookingPolicySettings $settings): Section
    {
        $default = fn (string|int|bool $value): string => __('catalog.hints.default', [
            'value' => is_bool($value) ? __($value ? 'catalog.yes' : 'catalog.no') : (string) $value,
        ]);
        $bool = fn (string $key, bool $value): Select => Select::make("policy_overrides.{$key}")
            ->label(__("catalog.overrides.{$key}"))
            ->options(['1' => __('catalog.yes'), '0' => __('catalog.no')])
            ->placeholder($default($value));
        $int = fn (string $key, int $value): TextInput => TextInput::make("policy_overrides.{$key}")
            ->label(__("catalog.overrides.{$key}"))->integer()->minValue(0)->placeholder($default($value));

        return Section::make(__('catalog.sections.overrides'))
            ->description(__('catalog.hints.overrides'))
            ->columns(2)->collapsible()->collapsed()
            ->schema([
                $int('cancellation_cutoff_minutes', $settings->cancellation_cutoff_minutes)->maxValue(10080),
                $bool('late_cancel_allowed', $settings->late_cancel_allowed),
                $int('booking_opens_days_ahead', $settings->booking_opens_days_ahead)->maxValue(365),
                $int('booking_closes_minutes_before', $settings->booking_closes_minutes_before)->maxValue(10080),
                $bool('waitlist_enabled', $settings->waitlist_enabled),
                $int('waitlist_max_size', $settings->waitlist_max_size)->maxValue(100),
            ]);
    }
}
