<?php

declare(strict_types=1);

namespace App\Modules\Billing\Filament\Resources;

use App\Filament\Support\Rejections;
use App\Modules\Billing\Actions\DeleteExpense;
use App\Modules\Billing\Actions\SaveExpense;
use App\Modules\Billing\Data\ExpenseData;
use App\Modules\Billing\Enums\ExpenseCategory;
use App\Modules\Billing\Filament\Resources\ExpenseResource\Pages\ManageExpenses;
use App\Modules\Billing\Models\Expense;
use App\Shared\BusinessTime;
use App\Shared\Money;
use App\View\Format;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * BL-02: business expenses with category, amount, date, note and an optional receipt.
 */
final class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): string
    {
        return __('billing.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('billing.expense.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('billing.expense.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Select::make('category')->label(__('billing.fields.category'))->options(ExpenseCategory::options())->required(),
            TextInput::make('amount')->label(__('billing.fields.amount'))->numeric()->required()
                ->minValue(0.01)->maxValue(1_000_000)->step(0.01)->suffix('€'),
            DatePicker::make('spent_on')->label(__('billing.fields.spent_on'))->required()
                ->default(fn (): string => app(BusinessTime::class)->today())
                ->maxDate(fn (): string => app(BusinessTime::class)->today()),
            FileUpload::make('attachment_path')->label(__('billing.fields.attachment'))
                ->disk(Expense::DISK)->directory(Expense::DIRECTORY)->visibility('private')
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(10_240),
            Textarea::make('note')->label(__('billing.fields.note'))->maxLength(500)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('spent_on', 'desc')
            ->columns([
                TextColumn::make('spent_on')->label(__('billing.fields.spent_on'))->date('d/m/Y')->sortable(),
                TextColumn::make('category')->label(__('billing.fields.category'))->badge()
                    ->formatStateUsing(fn (ExpenseCategory $state): string => $state->label()),
                TextColumn::make('amount_cents')->label(__('billing.fields.amount'))
                    ->formatStateUsing(fn (int $state): string => Format::money(Money::ofCents($state)))
                    ->summarize(Sum::make()->label(__('billing.fields.total'))
                        ->formatStateUsing(fn (mixed $state): string => Format::money(Money::ofCents((int) $state)))),
                TextColumn::make('note')->label(__('billing.fields.note'))->limit(60)->placeholder('—'),
                TextColumn::make('recorder.last_name')->label(__('billing.fields.recorded_by'))
                    ->formatStateUsing(fn (Expense $record): string => $record->recorder?->fullName() ?? '—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->label(__('billing.fields.category'))->options(ExpenseCategory::options()),
                Filter::make('period')->label(__('billing.fields.period'))
                    ->form([
                        DatePicker::make('from')->label(__('billing.fields.from'))
                            ->default(fn (): string => substr(app(BusinessTime::class)->today(), 0, 8).'01'),
                        DatePicker::make('until')->label(__('billing.fields.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['from'] ?? null), fn (Builder $q) => $q->whereDate('spent_on', '>=', (string) $data['from']))
                        ->when(filled($data['until'] ?? null), fn (Builder $q) => $q->whereDate('spent_on', '<=', (string) $data['until']))),
            ])
            ->actions([
                self::downloadAction(),
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data): array => [...$data, 'amount' => Money::ofCents((int) $data['amount_cents'])->toDecimal()])
                    ->using(fn (Expense $record, array $data, EditAction $action): Expense => self::save($data, $action, $record)),
                DeleteAction::make()->using(function (Expense $record): bool {
                    app(DeleteExpense::class)->handle($record);

                    return true;
                }),
            ]);
    }

    public static function createAction(): CreateAction
    {
        return CreateAction::make()->label(__('billing.actions.record_expense'))
            ->using(fn (array $data, CreateAction $action): Expense => self::save($data, $action));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function save(array $data, CreateAction|EditAction $action, ?Expense $record = null): Expense
    {
        return Rejections::run(fn (): Expense => app(SaveExpense::class)->handle(new ExpenseData(
            category: ExpenseCategory::from((string) $data['category']),
            amount_cents: Money::fromDecimal((string) $data['amount'])->cents,
            spent_on: substr((string) $data['spent_on'], 0, 10),
            note: filled($data['note'] ?? null) ? (string) $data['note'] : null,
            attachment_path: filled($data['attachment_path'] ?? null) ? (string) $data['attachment_path'] : null,
        ), auth()->id() === null ? null : (int) auth()->id(), $record), $action);
    }

    private static function downloadAction(): Action
    {
        return Action::make('download')->label(__('billing.actions.download_attachment'))->icon('heroicon-o-paper-clip')
            ->visible(fn (Expense $record): bool => $record->attachment_path !== null)
            ->authorize(fn (Expense $record): bool => auth()->user()?->can('view', $record) ?? false)
            ->action(fn (Expense $record): StreamedResponse => Storage::disk(Expense::DISK)->download((string) $record->attachment_path));
    }

    public static function getPages(): array
    {
        return ['index' => ManageExpenses::route('/')];
    }
}
