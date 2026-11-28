<?php

declare(strict_types=1);

namespace App\Modules\Communication\Filament\Pages;

use App\Modules\Booking\Contracts\InstructorRoster;
use App\Modules\Booking\Contracts\RosterEntry;
use App\Modules\Communication\Filament\Resources\NoteResource;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Enums\SessionStatus;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\Clock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

/**
 * ADR 0011: the instructor's own classes with their participants, and a quick note about
 * a participant's booking (RP-03).
 */
final class MyClasses extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = -10;

    protected static string $view = 'filament.communication.my-classes';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('page_MyClasses') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('communication.my_classes.title');
    }

    public function getTitle(): string
    {
        return __('communication.my_classes.title');
    }

    public function table(Table $table): Table
    {
        $now = app(Clock::class)->now();

        return $table
            ->query(ClassSession::query()->with('service')->where('instructor_id', $this->actor()->id))
            ->defaultSort('starts_at')
            ->columns([
                TextColumn::make('starts_at')->label(__('communication.my_classes.when'))
                    ->dateTime('D d/m H:i', timezone: 'Europe/Athens')->sortable(),
                TextColumn::make('service.name')->label(__('communication.my_classes.service')),
                TextColumn::make('participants')->label(__('communication.my_classes.participants'))
                    ->state(fn (ClassSession $record): string => count(app(InstructorRoster::class)->attendees($record->id)).' / '.$record->capacity),
                TextColumn::make('status')->label(__('communication.my_classes.status'))->badge()
                    ->formatStateUsing(fn (SessionStatus $state): string => $state->label()),
            ])
            ->filters([
                TernaryFilter::make('upcoming')->label(__('communication.my_classes.period'))
                    ->trueLabel(__('communication.my_classes.upcoming'))->falseLabel(__('communication.my_classes.past'))
                    ->default(true)
                    ->queries(
                        true: fn (Builder $q) => $q->where(new Expression('ends_at'), '>=', $now)->orderBy('starts_at'),
                        false: fn (Builder $q) => $q->where(new Expression('ends_at'), '<', $now)->reorder('starts_at', 'desc'),
                        blank: fn (Builder $q): Builder => $q,
                    ),
            ])
            ->actions([$this->participantsAction(), $this->noteAction()]);
    }

    private function participantsAction(): Action
    {
        return Action::make('participants')->label(__('communication.my_classes.participants'))->icon('heroicon-o-users')
            ->modalSubmitAction(false)->modalCancelActionLabel(__('communication.my_classes.close'))
            ->modalContent(fn (ClassSession $record): View => view('filament.communication.participants', [
                'attendees' => app(InstructorRoster::class)->attendees($record->id),
            ]));
    }

    private function noteAction(): Action
    {
        return Action::make('note')->label(__('communication.notes.create'))->icon('heroicon-o-pencil-square')
            ->visible(fn (ClassSession $record): bool => app(InstructorRoster::class)->attendees($record->id) !== [])
            ->form(fn (ClassSession $record): array => [
                Select::make('booking_id')->label(__('communication.notes.customer'))->required()
                    ->options(collect(app(InstructorRoster::class)->attendees($record->id))
                        ->mapWithKeys(fn (RosterEntry $e): array => [$e->bookingId => $e->name])->all()),
                Textarea::make('body')->label(__('communication.notes.body'))->required()->rows(5)->maxLength(5000),
                Toggle::make('visible_to_customer')->label(__('communication.notes.visible_to_customer')),
            ])
            ->action(function (ClassSession $record, array $data, Action $action): void {
                $entry = collect(app(InstructorRoster::class)->attendees($record->id))
                    ->first(fn (RosterEntry $e): bool => $e->bookingId === (int) $data['booking_id']);
                abort_unless($entry instanceof RosterEntry, 404);

                NoteResource::save([...$data, 'user_id' => $entry->userId], $action);
                Notification::make()->success()->title(__('communication.notes.saved'))->send();
            });
    }

    private function actor(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
