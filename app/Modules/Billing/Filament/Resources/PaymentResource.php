<?php

declare(strict_types=1);

namespace App\Modules\Billing\Filament\Resources;

use App\Filament\Support\Rejections;
use App\Modules\Billing\Actions\RecordPayment;
use App\Modules\Billing\Data\PaymentData;
use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Billing\Enums\PaymentStatus;
use App\Modules\Billing\Filament\Resources\PaymentResource\Pages\ListPayments;
use App\Modules\Billing\Models\Payment;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Shared\BusinessTime;
use App\Shared\Money;
use App\View\Format;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

/**
 * BL-01: payments collected, with a running total for the filtered period.
 */
final class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationGroup(): string
    {
        return __('billing.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('billing.payment.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('billing.payment.plural');
    }

    /**
     * @return Builder<Payment>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'recorder']);
    }

    public static function table(Table $table): Table
    {
        $tz = app(BusinessTime::class)->timezone();

        return $table
            ->defaultSort('paid_at', 'desc')
            ->columns([
                TextColumn::make('paid_at')->label(__('billing.fields.paid_at'))->dateTime('d/m/Y H:i', timezone: $tz)->sortable(),
                TextColumn::make('user.last_name')->label(__('billing.fields.customer'))
                    ->formatStateUsing(fn (Payment $record): string => $record->user->fullName())
                    ->searchable(['first_name', 'last_name', 'email']),
                TextColumn::make('amount_cents')->label(__('billing.fields.amount'))
                    ->formatStateUsing(fn (int $state): string => Format::money(Money::ofCents($state)))
                    ->summarize(Sum::make()->label(__('billing.fields.total'))
                        ->formatStateUsing(fn (mixed $state): string => Format::money(Money::ofCents((int) $state)))),
                TextColumn::make('method')->label(__('billing.fields.method'))->badge()
                    ->formatStateUsing(fn (PaymentMethod $state): string => $state->label()),
                TextColumn::make('payable_type')->label(__('billing.fields.for'))
                    ->formatStateUsing(fn (?string $state): string => __('billing.payables.'.($state ?? 'other')))
                    ->placeholder(__('billing.payables.other')),
                TextColumn::make('status')->label(__('billing.fields.status'))
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('recorder.last_name')->label(__('billing.fields.recorded_by'))
                    ->formatStateUsing(fn (Payment $record): string => $record->recorder?->fullName() ?? '—')->toggleable(),
                TextColumn::make('note')->label(__('billing.fields.note'))->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('method')->label(__('billing.fields.method'))
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $m): array => [$m->value => $m->label()])->all()),
                Filter::make('period')->label(__('billing.fields.period'))
                    ->form([
                        DatePicker::make('from')->label(__('billing.fields.from'))->default(now()->startOfMonth()),
                        DatePicker::make('until')->label(__('billing.fields.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => self::period($query, $data)),
            ]);
    }

    /**
     * Payments collected between two local dates (inclusive).
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $data
     * @return Builder<TModel>
     */
    private static function period(Builder $query, array $data): Builder
    {
        $time = app(BusinessTime::class);
        $from = is_string($data['from'] ?? null) ? substr($data['from'], 0, 10) : null;
        $until = is_string($data['until'] ?? null) ? substr($data['until'], 0, 10) : null;

        return $query
            ->when($from !== null, fn (Builder $q) => $q->where(new Expression('paid_at'), '>=', $time->toUtc((string) $from, '00:00')))
            ->when($until !== null, fn (Builder $q) => $q->where(new Expression('paid_at'), '<', $time->toUtc((string) $until, '00:00')->addDay()));
    }

    public static function recordAction(): Action
    {
        return Action::make('record')->label(__('billing.actions.record'))->icon('heroicon-o-plus')
            ->authorize(fn (): bool => auth()->user()?->can('create', Payment::class) ?? false)
            ->form([
                Select::make('user_id')->label(__('billing.fields.customer'))->required()->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => User::role(Role::Customer->value)->whereNull('anonymized_at')
                        ->where(fn (Builder $q) => $q->where('last_name', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->limit(20)->get()->mapWithKeys(fn (User $u): array => [$u->id => $u->fullName().' · '.$u->email])->all())
                    ->getOptionLabelUsing(fn (mixed $value): ?string => User::query()->whereKey($value)->first()?->fullName()),
                TextInput::make('amount')->label(__('billing.fields.amount'))->numeric()->required()->minValue(0.01)->step(0.01)->suffix('€'),
                Select::make('method')->label(__('billing.fields.method'))->required()
                    ->options(PaymentMethod::deskOptions())->default(PaymentMethod::Cash->value),
                TextInput::make('note')->label(__('billing.fields.note'))->maxLength(255),
            ])
            ->action(function (array $data, Action $action): void {
                Rejections::run(fn (): Payment => app(RecordPayment::class)->handle(new PaymentData(
                    user_id: (int) $data['user_id'],
                    amount_cents: Money::fromDecimal((string) $data['amount'])->cents,
                    method: PaymentMethod::from((string) $data['method']),
                    recorded_by: (int) auth()->id(),
                    note: filled($data['note'] ?? null) ? (string) $data['note'] : null,
                )), $action);

                Notification::make()->success()->title(__('billing.notices.recorded'))->send();
            });
    }

    public static function getPages(): array
    {
        return ['index' => ListPayments::route('/')];
    }
}
