<?php

declare(strict_types=1);

namespace App\Modules\Communication\Filament\Resources;

use App\Modules\Communication\Actions\SaveEmailTemplate;
use App\Modules\Communication\Enums\NotificationType;
use App\Modules\Communication\Filament\Resources\EmailTemplateResource\Pages\ManageEmailTemplates;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Support\TemplateRenderer;
use App\Modules\Identity\Enums\Locale;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * CM-01: staff reword the customer notifications. Rows exist for every type (migration);
 * there is nothing to create or delete, only to edit.
 */
final class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    public static function getNavigationGroup(): string
    {
        return __('communication.admin.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('communication.admin.template');
    }

    public static function getPluralModelLabel(): string
    {
        return __('communication.admin.templates');
    }

    public static function form(Form $form): Form
    {
        $fields = [
            Placeholder::make('help')->hiddenLabel()->content(fn (?EmailTemplate $record): string => __('communication.admin.placeholders', [
                'list' => implode(', ', array_map(fn (string $p): string => ':'.$p, $record?->type->placeholders() ?? [])),
            ])),
        ];

        foreach (Locale::cases() as $locale) {
            $fields[] = TextInput::make("subject.{$locale->value}")->label(__('communication.admin.subject')." ({$locale->label()})")
                ->maxLength(200)->placeholder(fn (?EmailTemplate $record): string => self::default($record, 'subject', $locale));
            $fields[] = Textarea::make("body.{$locale->value}")->label(__('communication.admin.body')." ({$locale->label()})")
                ->rows(6)->maxLength(3000)->placeholder(fn (?EmailTemplate $record): string => self::default($record, 'body', $locale));
        }

        return $form->schema($fields)->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('type')->label(__('communication.preferences.type'))
                    ->formatStateUsing(fn (NotificationType $state): string => $state->label()),
                IconColumn::make('customized')->label(__('communication.admin.customized'))->boolean()
                    ->getStateUsing(fn (EmailTemplate $record): bool => $record->getTranslations('subject') !== [] || $record->getTranslations('body') !== []),
            ])
            ->actions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, EmailTemplate $record): array => [
                        'subject' => $record->getTranslations('subject'),
                        'body' => $record->getTranslations('body'),
                    ])
                    ->using(fn (EmailTemplate $record, array $data): Model => app(SaveEmailTemplate::class)
                        ->handle($record, (array) ($data['subject'] ?? []), (array) ($data['body'] ?? []))),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageEmailTemplates::route('/')];
    }

    private static function default(?EmailTemplate $record, string $part, Locale $locale): string
    {
        return $record instanceof EmailTemplate ? app(TemplateRenderer::class)->defaultText($record->type, $part, $locale->value) : '';
    }
}
