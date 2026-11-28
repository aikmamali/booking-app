<?php

declare(strict_types=1);

namespace App\Modules\Communication\Filament\Resources;

use App\Filament\Support\Rejections;
use App\Modules\Catalog\Models\Service;
use App\Modules\Communication\Actions\CountBroadcastRecipients;
use App\Modules\Communication\Actions\QueueBroadcast;
use App\Modules\Communication\Data\BroadcastAudience;
use App\Modules\Communication\Data\BroadcastData;
use App\Modules\Communication\Enums\BroadcastStatus;
use App\Modules\Communication\Enums\Channel;
use App\Modules\Communication\Filament\Resources\BroadcastResource\Pages\ListBroadcasts;
use App\Modules\Communication\Models\Broadcast;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * CM-03: bulk email / SMS to a filtered audience of consenting customers, with history.
 */
final class BroadcastResource extends Resource
{
    protected static ?string $model = Broadcast::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    public static function getNavigationGroup(): string
    {
        return __('communication.admin.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('communication.broadcasts.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('communication.broadcasts.plural');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('creator'))
            ->columns([
                TextColumn::make('created_at')->label(__('communication.broadcasts.created_at'))
                    ->dateTime('d/m/Y H:i', timezone: 'Europe/Athens')->sortable(),
                TextColumn::make('subject')->label(__('communication.broadcasts.subject'))->limit(50)->searchable(),
                TextColumn::make('channels')->label(__('communication.broadcasts.channels'))->badge()
                    ->formatStateUsing(fn (string $state): string => Channel::from($state)->label()),
                TextColumn::make('sent_count')->label(__('communication.broadcasts.sent'))
                    ->formatStateUsing(fn (Broadcast $record): string => $record->sent_count.' / '.$record->recipients_count),
                TextColumn::make('status')->label(__('communication.broadcasts.status_label'))->badge()
                    ->formatStateUsing(fn (BroadcastStatus $state): string => $state->label())
                    ->color(fn (BroadcastStatus $state): string => $state === BroadcastStatus::Completed ? 'success' : 'warning'),
                TextColumn::make('creator.last_name')->label(__('communication.broadcasts.created_by'))
                    ->formatStateUsing(fn (Broadcast $record): string => $record->creator?->fullName() ?? '—'),
            ])
            ->actions([ViewAction::make()]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->columns(1)->schema([
            TextEntry::make('subject')->label(__('communication.broadcasts.subject')),
            TextEntry::make('body')->label(__('communication.broadcasts.body'))->prose(),
            TextEntry::make('sent_count')->label(__('communication.broadcasts.sent'))
                ->formatStateUsing(fn (Broadcast $record): string => $record->sent_count.' / '.$record->recipients_count),
        ]);
    }

    public static function sendAction(): Action
    {
        return Action::make('send')->label(__('communication.broadcasts.create'))->icon('heroicon-o-paper-airplane')
            ->authorize(fn (): bool => auth()->user()?->can('create', Broadcast::class) ?? false)
            ->modalWidth('2xl')
            ->modalSubmitActionLabel(__('communication.broadcasts.send'))
            ->form([
                TextInput::make('subject')->label(__('communication.broadcasts.subject'))->required()->maxLength(150),
                Textarea::make('body')->label(__('communication.broadcasts.body'))->required()->rows(6)->maxLength(5000)
                    ->helperText(__('communication.broadcasts.body_hint')),
                CheckboxList::make('channels')->label(__('communication.broadcasts.channels'))->required()
                    ->options([Channel::Mail->value => Channel::Mail->label(), Channel::Sms->value => Channel::Sms->label()])
                    ->default([Channel::Mail->value]),
                Section::make(__('communication.broadcasts.audience'))
                    ->description(__('communication.broadcasts.audience_hint'))
                    ->schema([
                        Toggle::make('active_membership')->label(__('communication.broadcasts.active_membership'))->live(),
                        TextInput::make('expiring_within_days')->label(__('communication.broadcasts.expiring_within_days'))
                            ->integer()->minValue(0)->maxValue(365)->live(onBlur: true),
                        Select::make('service_id')->label(__('communication.broadcasts.service'))->live()
                            ->options(fn (): array => Service::query()->where('is_active', true)->get()
                                ->mapWithKeys(fn (Service $s): array => [$s->id => $s->name])->all()),
                        Placeholder::make('recipients')->label(__('communication.broadcasts.recipients'))
                            ->content(fn (Get $get): string => __('communication.broadcasts.recipients_count', [
                                'count' => app(CountBroadcastRecipients::class)->handle(self::audience($get('active_membership'), $get('expiring_within_days'), $get('service_id'))),
                            ])),
                    ]),
            ])
            ->action(function (array $data, Action $action): void {
                $broadcast = Rejections::run(fn (): Broadcast => app(QueueBroadcast::class)->handle(new BroadcastData(
                    subject: (string) $data['subject'],
                    body: (string) $data['body'],
                    channels: array_values(array_map(fn (mixed $c): Channel => Channel::from((string) $c), (array) $data['channels'])),
                    audience: self::audience($data['active_membership'] ?? false, $data['expiring_within_days'] ?? null, $data['service_id'] ?? null),
                ), auth()->id() === null ? null : (int) auth()->id()), $action);

                Notification::make()->success()
                    ->title(__('communication.broadcasts.queued', ['count' => $broadcast->recipients_count]))->send();
            });
    }

    private static function audience(mixed $active, mixed $expiring, mixed $service): BroadcastAudience
    {
        return new BroadcastAudience(
            active_membership: (bool) $active,
            expiring_within_days: filled($expiring) ? (int) $expiring : null,
            service_id: filled($service) ? (int) $service : null,
        );
    }

    public static function getPages(): array
    {
        return ['index' => ListBroadcasts::route('/')];
    }
}
