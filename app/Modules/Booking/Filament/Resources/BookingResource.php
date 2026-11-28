<?php

declare(strict_types=1);

namespace App\Modules\Booking\Filament\Resources;

use App\Filament\Support\Rejections;
use App\Modules\Booking\Actions\BookInBulk;
use App\Modules\Booking\Actions\BookSession;
use App\Modules\Booking\Actions\CancelBooking;
use App\Modules\Booking\Actions\MarkAttendance;
use App\Modules\Booking\Data\BookingRequest;
use App\Modules\Booking\Data\BulkBookingRow;
use App\Modules\Booking\Data\CancellationRequest;
use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Filament\Resources\BookingResource\Pages\ListBookings;
use App\Modules\Booking\Models\Booking;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\BusinessTime;
use App\Shared\Clock;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 0;

    public static function getNavigationGroup(): string
    {
        return __('booking.admin.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('booking.admin.booking');
    }

    public static function getPluralModelLabel(): string
    {
        return __('booking.admin.bookings');
    }

    /**
     * @return Builder<Booking>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'session.service']);
    }

    public static function table(Table $table): Table
    {
        $tz = app(BusinessTime::class)->timezone();

        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('session.starts_at')->label(__('booking.fields.when'))->dateTime('D d/m/Y H:i', timezone: $tz),
                TextColumn::make('session.service.name')->label(__('catalog.service.label')),
                TextColumn::make('user.last_name')->label(__('booking.fields.customer'))
                    ->formatStateUsing(fn (Booking $record): string => $record->user->fullName())
                    ->searchable(['first_name', 'last_name', 'email']),
                TextColumn::make('status')->label(__('booking.fields.status'))->badge()
                    ->formatStateUsing(fn (BookingStatus $state): string => $state->label())
                    ->color(fn (BookingStatus $state): string => match ($state) {
                        BookingStatus::Confirmed, BookingStatus::Attended => 'success',
                        BookingStatus::Cancelled, BookingStatus::NoShow, BookingStatus::Expired => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('source')->label(__('booking.fields.source'))
                    ->formatStateUsing(fn (BookingSource $state): string => __('booking.sources.'.$state->value))->toggleable(),
                TextColumn::make('public_id')->label(__('booking.fields.reference'))
                    ->formatStateUsing(fn (Booking $record): string => $record->reference())->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('session')->label(__('scheduling.session.label'))
                    ->form([TextInput::make('session')->hidden()])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['session'] ?? null)
                        ? $query->whereRelation('session', 'public_id', $data['session'])
                        : $query),
                SelectFilter::make('status')->label(__('booking.fields.status'))
                    ->options(collect(BookingStatus::cases())->mapWithKeys(fn (BookingStatus $s): array => [$s->value => $s->label()])->all()),
            ])
            ->actions([ActionGroup::make([
                self::attendanceAction(BookingStatus::Attended),
                self::attendanceAction(BookingStatus::NoShow),
                self::cancelAction(),
            ])]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBookings::route('/')];
    }

    public static function bookAction(): Action
    {
        return Action::make('book')->label(__('booking.admin.book_customer'))->icon('heroicon-o-plus')
            ->authorize(fn (): bool => auth()->user()?->can('create', Booking::class) ?? false)
            ->form([
                Select::make('user_id')->label(__('booking.fields.customer'))->required()->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => self::customers($search))
                    ->getOptionLabelUsing(fn (mixed $value): ?string => User::query()->whereKey($value)->first()?->fullName()),
                Select::make('session_id')->label(__('scheduling.session.label'))->required()->searchable()
                    ->options(fn (): array => self::upcomingSessions()),
                TextInput::make('override_reason')->label(__('booking.fields.override_reason'))->maxLength(255)
                    ->helperText(__('booking.admin.override_hint')),
            ])
            ->action(function (array $data, Action $action): void {
                Rejections::run(fn (): Booking => app(BookSession::class)->handle(new BookingRequest(
                    user_id: (int) $data['user_id'],
                    session_id: (int) $data['session_id'],
                    source: BookingSource::Staff,
                    actor_id: (int) auth()->id(),
                    override_reason: filled($data['override_reason'] ?? null) ? (string) $data['override_reason'] : null,
                )), $action);

                Notification::make()->success()->title(__('booking.notices.booked'))->send();
            });
    }

    /**
     * BK-10: customers × sessions in one step, with a per-row report of what failed.
     */
    public static function bulkBookAction(): Action
    {
        return Action::make('bulkBook')->label(__('booking.bulk.action'))->icon('heroicon-o-user-group')
            ->authorize(fn (): bool => auth()->user()?->can('create', Booking::class) ?? false)
            ->modalDescription(__('booking.bulk.description', ['max' => BookInBulk::MAX_PAIRS]))
            ->form([
                Select::make('user_ids')->label(__('booking.bulk.customers'))->required()->multiple()->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => self::customers($search))
                    ->getOptionLabelsUsing(fn (array $values): array => User::query()->whereKey($values)->get()
                        ->mapWithKeys(fn (User $u): array => [$u->id => $u->fullName()])->all()),
                Select::make('session_ids')->label(__('booking.bulk.sessions'))->required()->multiple()->searchable()
                    ->options(fn (): array => self::upcomingSessions()),
                TextInput::make('override_reason')->label(__('booking.fields.override_reason'))->maxLength(255)
                    ->helperText(__('booking.admin.override_hint')),
            ])
            ->action(function (array $data, Action $action): void {
                $rows = Rejections::run(fn (): array => app(BookInBulk::class)->handle(
                    array_values(array_map(intval(...), (array) $data['user_ids'])),
                    array_values(array_map(intval(...), (array) $data['session_ids'])),
                    (int) auth()->id(),
                    filled($data['override_reason'] ?? null) ? (string) $data['override_reason'] : null,
                ), $action);

                self::reportBulk($rows);
            });
    }

    /**
     * @param  list<BulkBookingRow>  $rows
     */
    private static function reportBulk(array $rows): void
    {
        $failed = array_values(array_filter($rows, fn (BulkBookingRow $r): bool => ! $r->succeeded()));
        $names = User::query()->whereKey(array_map(fn (BulkBookingRow $r): int => $r->userId, $failed))->get()
            ->mapWithKeys(fn (User $u): array => [$u->id => $u->fullName()]);
        $sessions = self::upcomingSessions();

        $lines = array_map(fn (BulkBookingRow $r): string => e(($names[$r->userId] ?? '?').' · '.($sessions[$r->sessionId] ?? '#'.$r->sessionId).': '.$r->error), $failed);

        Notification::make()
            ->status($failed === [] ? 'success' : 'warning')
            ->title(__('booking.bulk.result', ['booked' => count($rows) - count($failed), 'failed' => count($failed)]))
            ->body(implode('<br>', $lines))
            ->persistent()
            ->send();
    }

    private static function cancelAction(): TableAction
    {
        return TableAction::make('cancel')->label(__('booking.admin.cancel'))->icon('heroicon-o-x-circle')->color('danger')
            ->visible(fn (Booking $record): bool => $record->status->isActive())
            ->authorize(fn (Booking $record): bool => auth()->user()?->can('manage', $record) ?? false)
            ->requiresConfirmation()
            ->form([
                TextInput::make('reason')->label(__('booking.fields.cancel_reason'))->maxLength(255),
                TextInput::make('refund_override_reason')->label(__('booking.admin.refund_anyway'))->maxLength(255),
            ])
            ->action(function (Booking $record, array $data, TableAction $action): void {
                Rejections::run(fn (): Booking => app(CancelBooking::class)->handle($record, new CancellationRequest(
                    actor_id: (int) auth()->id(),
                    reason: filled($data['reason'] ?? null) ? (string) $data['reason'] : null,
                    by_staff: true,
                    refund_override_reason: filled($data['refund_override_reason'] ?? null) ? (string) $data['refund_override_reason'] : null,
                )), $action);

                Notification::make()->success()->title(__('booking.notices.cancelled'))->send();
            });
    }

    private static function attendanceAction(BookingStatus $outcome): TableAction
    {
        return TableAction::make($outcome->value)->label(__('booking.admin.'.$outcome->value))
            ->icon($outcome === BookingStatus::Attended ? 'heroicon-o-check' : 'heroicon-o-user-minus')
            ->visible(fn (Booking $record): bool => $record->status === BookingStatus::Confirmed
                && $record->session->starts_at->lte(app(Clock::class)->now()))
            ->authorize(fn (): bool => auth()->user()?->can('markAttendance', Booking::class) ?? false)
            ->action(function (Booking $record, TableAction $action) use ($outcome): void {
                Rejections::run(fn (): Booking => app(MarkAttendance::class)->handle($record, $outcome, (int) auth()->id()), $action);

                Notification::make()->success()->title(__('booking.notices.marked'))->send();
            });
    }

    /**
     * @return array<int, string>
     */
    private static function customers(string $search): array
    {
        return User::role(Role::Customer->value)->whereNull('anonymized_at')
            ->where(fn (Builder $q) => $q->where('last_name', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
            ->orderBy('last_name')->limit(20)->get()
            ->mapWithKeys(fn (User $u): array => [$u->id => $u->fullName().' · '.$u->email])->all();
    }

    /**
     * @return array<int, string>
     */
    private static function upcomingSessions(): array
    {
        $time = app(BusinessTime::class);

        return ClassSession::query()->with('service')->scheduled()
            ->where('starts_at', '>', app(Clock::class)->now())
            ->where('starts_at', '<', app(Clock::class)->now()->addDays(31))
            ->orderBy('starts_at')->get()
            ->mapWithKeys(fn (ClassSession $s): array => [
                $s->id => $time->toLocal($s->starts_at)->format('D d/m H:i').' · '.$s->service->name,
            ])->all();
    }
}
