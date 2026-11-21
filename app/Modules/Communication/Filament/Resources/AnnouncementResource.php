<?php

declare(strict_types=1);

namespace App\Modules\Communication\Filament\Resources;

use App\Filament\Support\Translatable;
use App\Modules\Communication\Actions\SaveAnnouncement;
use App\Modules\Communication\Filament\Resources\AnnouncementResource\Pages\ManageAnnouncements;
use App\Modules\Communication\Models\Announcement;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * CM-04: carousel of news on the customer home page.
 */
final class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    public static function getNavigationGroup(): string
    {
        return __('communication.admin.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('communication.announcements.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('communication.announcements.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            ...Translatable::text('title', __('communication.admin.fields.title')),
            ...Translatable::textarea('body', __('communication.admin.fields.body')),
            FileUpload::make('image_path')->label(__('communication.admin.fields.image'))
                ->image()->disk('public')->directory('announcements')->maxSize(2048)->columnSpanFull(),
            DateTimePicker::make('published_from')->label(__('communication.admin.fields.published_from'))
                ->required()->seconds(false)->default(now()),
            DateTimePicker::make('published_until')->label(__('communication.admin.fields.published_until'))
                ->seconds(false)->after('published_from'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('position')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('title')->label(__('communication.admin.fields.title')),
                TextColumn::make('published_from')->label(__('communication.admin.fields.published_from'))->dateTime('d/m/Y H:i'),
                TextColumn::make('published_until')->label(__('communication.admin.fields.published_until'))->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->actions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, Announcement $record): array => [
                        ...$data,
                        'title' => $record->getTranslations('title'),
                        'body' => $record->getTranslations('body'),
                    ])
                    ->using(fn (Announcement $record, array $data): Model => self::save($record, $data)),
                DeleteAction::make(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function save(?Announcement $announcement, array $data): Announcement
    {
        $until = $data['published_until'] ?? null;

        return app(SaveAnnouncement::class)->handle(
            $announcement,
            (array) $data['title'],
            (array) ($data['body'] ?? []),
            is_string($data['image_path'] ?? null) ? $data['image_path'] : null,
            (string) $data['published_from'],
            $until === null || $until === '' ? null : (string) $until,
        );
    }

    public static function getPages(): array
    {
        return ['index' => ManageAnnouncements::route('/')];
    }
}
