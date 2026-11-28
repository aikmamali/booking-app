<?php

declare(strict_types=1);

namespace App\Modules\Communication\Filament\Resources;

use App\Filament\Support\Rejections;
use App\Modules\Booking\Contracts\InstructorRoster;
use App\Modules\Communication\Actions\DeleteNote;
use App\Modules\Communication\Actions\SaveNote;
use App\Modules\Communication\Data\NoteData;
use App\Modules\Communication\Filament\Resources\NoteResource\Pages\ManageNotes;
use App\Modules\Communication\Models\Note;
use App\Modules\Communication\Support\NoteAccess;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * RP-03: trainer notes (exercise log). Instructors see the notes about customers of their
 * own classes and change only their own (ADR 0011).
 */
final class NoteResource extends Resource
{
    protected static ?string $model = Note::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): string
    {
        return __('communication.notes.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('communication.notes.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('communication.notes.plural');
    }

    /**
     * @return Builder<Note>
     */
    public static function getEloquentQuery(): Builder
    {
        $customers = app(NoteAccess::class)->customerIds(self::actor());

        return parent::getEloquentQuery()->with(['customer', 'author'])
            ->when($customers !== null, fn (Builder $q) => $q->whereIn('user_id', $customers));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('user_id')->label(__('communication.notes.customer'))->required()->searchable()
                ->getSearchResultsUsing(fn (string $search): array => self::customerOptions($search))
                ->getOptionLabelUsing(fn (mixed $value): ?string => User::query()->whereKey($value)->first()?->fullName())
                ->live()->afterStateUpdated(fn (Set $set): mixed => $set('booking_id', null)),
            Select::make('booking_id')->label(__('communication.notes.booking'))
                ->helperText(__('communication.notes.booking_hint'))
                ->options(fn (Get $get): array => filled($get('user_id'))
                    ? app(InstructorRoster::class)->bookingChoices((int) $get('user_id'), app(NoteAccess::class)->instructorScope(self::actor()))
                    : []),
            Textarea::make('body')->label(__('communication.notes.body'))->required()->rows(6)->maxLength(5000),
            Toggle::make('visible_to_customer')->label(__('communication.notes.visible_to_customer'))
                ->helperText(__('communication.notes.visible_hint')),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('communication.notes.created_at'))
                    ->dateTime('d/m/Y H:i', timezone: 'Europe/Athens')->sortable(),
                TextColumn::make('customer.last_name')->label(__('communication.notes.customer'))
                    ->formatStateUsing(fn (Note $record): string => $record->customer->fullName())
                    ->searchable(['first_name', 'last_name', 'email']),
                TextColumn::make('booking_id')->label(__('communication.notes.booking'))->placeholder('—')
                    ->formatStateUsing(fn (Note $record): string => app(InstructorRoster::class)->labels([(int) $record->booking_id])[(int) $record->booking_id] ?? '—'),
                TextColumn::make('body')->label(__('communication.notes.body'))->limit(80)->wrap(),
                IconColumn::make('visible_to_customer')->label(__('communication.notes.visible_short'))->boolean(),
                TextColumn::make('author.last_name')->label(__('communication.notes.author'))
                    ->formatStateUsing(fn (Note $record): string => $record->author?->fullName() ?? '—'),
            ])
            ->filters([
                TernaryFilter::make('visible_to_customer')->label(__('communication.notes.visible_short')),
            ])
            ->actions([
                EditAction::make()->using(fn (Note $record, array $data, EditAction $action): Note => self::save($data, $action, $record)),
                DeleteAction::make()->using(function (Note $record): bool {
                    app(DeleteNote::class)->handle($record, self::actor());

                    return true;
                }),
            ]);
    }

    /**
     * @param  array<string, mixed>  $prefill
     */
    public static function createAction(array $prefill = []): CreateAction
    {
        return CreateAction::make()->label(__('communication.notes.create'))
            ->fillForm($prefill)
            ->using(fn (array $data, CreateAction $action): Note => self::save($data, $action));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function save(array $data, Action|TableAction $action, ?Note $record = null): Note
    {
        return Rejections::run(fn (): Note => app(SaveNote::class)->handle(new NoteData(
            user_id: (int) $data['user_id'],
            body: (string) $data['body'],
            visible_to_customer: (bool) ($data['visible_to_customer'] ?? false),
            booking_id: filled($data['booking_id'] ?? null) ? (int) $data['booking_id'] : null,
        ), self::actor(), $record), $action);
    }

    public static function getPages(): array
    {
        return ['index' => ManageNotes::route('/')];
    }

    /**
     * @return array<int, string>
     */
    private static function customerOptions(string $search): array
    {
        $ids = app(NoteAccess::class)->customerIds(self::actor());

        return User::role(Role::Customer->value)->whereNull('anonymized_at')
            ->when($ids !== null, fn (Builder $q) => $q->whereKey($ids))
            ->where(fn (Builder $q) => $q->where('last_name', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->orderBy('last_name')->limit(20)->get()
            ->mapWithKeys(fn (User $u): array => [$u->id => $u->fullName()])
            ->all();
    }

    private static function actor(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
