<?php

declare(strict_types=1);

use App\Modules\Billing\Actions\DeleteExpense;
use App\Modules\Billing\Actions\SaveExpense;
use App\Modules\Billing\Data\ExpenseData;
use App\Modules\Billing\Enums\ExpenseCategory;
use App\Modules\Billing\Exceptions\PaymentRejected;
use App\Modules\Billing\Filament\Resources\ExpenseResource\Pages\ManageExpenses;
use App\Modules\Billing\Models\Expense;
use App\Modules\Identity\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo('2027-03-20 08:00:00');
    Storage::fake(Expense::DISK);
});

function expenseData(array $overrides = []): ExpenseData
{
    return new ExpenseData(...[
        'category' => ExpenseCategory::Rent,
        'amount_cents' => 80_000,
        'spent_on' => '2027-03-01',
        'note' => ' Μάρτιος ',
        ...$overrides,
    ]);
}

it('records an expense with who entered it', function (): void {
    $admin = User::factory()->admin()->create();

    $expense = app(SaveExpense::class)->handle(expenseData(), $admin->id);

    expect($expense->category)->toBe(ExpenseCategory::Rent)
        ->and($expense->amount()->toDecimal())->toBe('800.00')
        ->and($expense->spent_on->toDateString())->toBe('2027-03-01')
        ->and($expense->note)->toBe('Μάρτιος')
        ->and($expense->recorded_by)->toBe($admin->id)
        ->and($expense->public_id)->toHaveLength(26);
    $this->assertDatabaseHas('activity_log', ['subject_type' => $expense->getMorphClass(), 'subject_id' => $expense->id, 'event' => 'created']);
});

it('refuses a zero amount or a future date', function (array $overrides, string $error): void {
    expect(fn () => app(SaveExpense::class)->handle(expenseData($overrides), null))
        ->toThrow(PaymentRejected::class, $error);
})->with([
    'zero' => [['amount_cents' => 0], 'billing.errors.amount'],
    'tomorrow' => [['spent_on' => '2027-03-21'], 'billing.errors.expense_in_future'],
]);

it('accepts today in Athens even when UTC is still yesterday', function (): void {
    $this->travelTo('2027-03-20 22:30:00'); // 00:30 Athens on 21/3

    expect(app(SaveExpense::class)->handle(expenseData(['spent_on' => '2027-03-21']), null)->spent_on->toDateString())->toBe('2027-03-21');
});

it('deletes a replaced attachment and keeps the creator on corrections', function (): void {
    $creator = User::factory()->admin()->create();
    Storage::disk(Expense::DISK)->put('expenses/old.pdf', 'x');
    Storage::disk(Expense::DISK)->put('expenses/new.pdf', 'y');
    $expense = app(SaveExpense::class)->handle(expenseData(['attachment_path' => 'expenses/old.pdf']), $creator->id);

    app(SaveExpense::class)->handle(expenseData(['amount_cents' => 85_000, 'attachment_path' => 'expenses/new.pdf']), null, $expense);

    expect($expense->refresh()->amount_cents)->toBe(85_000)
        ->and($expense->recorded_by)->toBe($creator->id);
    Storage::disk(Expense::DISK)->assertMissing('expenses/old.pdf');
    Storage::disk(Expense::DISK)->assertExists('expenses/new.pdf');
});

it('soft deletes so the audit trail keeps the entry', function (): void {
    $expense = Expense::factory()->create();

    app(DeleteExpense::class)->handle($expense);

    expect(Expense::query()->find($expense->id))->toBeNull()
        ->and(Expense::withTrashed()->find($expense->id))->not->toBeNull();
});

describe('admin', function (): void {
    beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

    it('records an expense with a receipt from the list page', function (): void {
        Livewire::test(ManageExpenses::class)
            ->callAction('create', [
                'category' => ExpenseCategory::Utilities->value,
                'amount' => '123.45',
                'spent_on' => '2027-03-18',
                'note' => 'ΔΕΗ',
                'attachment_path' => UploadedFile::fake()->create('dei.pdf', 100, 'application/pdf'),
            ])
            ->assertHasNoActionErrors();

        $expense = Expense::query()->sole();
        expect($expense->amount_cents)->toBe(12_345)
            ->and($expense->attachment_path)->toStartWith('expenses/');
        Storage::disk(Expense::DISK)->assertExists((string) $expense->attachment_path);
    });

    it('shows a future date as an error', function (): void {
        Livewire::test(ManageExpenses::class)
            ->callAction('create', ['category' => 'rent', 'amount' => '10', 'spent_on' => '2027-04-01'])
            ->assertHasActionErrors(['spent_on']);

        expect(Expense::query()->count())->toBe(0);
    });

    it('edits, totals and downloads', function (): void {
        Storage::disk(Expense::DISK)->put('expenses/r.pdf', 'pdf');
        $a = Expense::factory()->create(['amount_cents' => 10_000, 'spent_on' => '2027-03-02', 'attachment_path' => 'expenses/r.pdf']);
        Expense::factory()->create(['amount_cents' => 5_050, 'spent_on' => '2027-03-03']);
        Expense::factory()->create(['amount_cents' => 99_999, 'spent_on' => '2027-02-27']); // previous month

        Livewire::test(ManageExpenses::class)
            ->assertCanSeeTableRecords([$a])
            ->assertSee('150,50')
            ->callTableAction('edit', $a, ['category' => 'equipment', 'amount' => '200', 'spent_on' => '2027-03-02'])
            ->assertHasNoTableActionErrors()
            ->callTableAction('download', $a)
            ->assertFileDownloaded('r.pdf');

        expect($a->refresh()->amount_cents)->toBe(20_000)
            ->and($a->category)->toBe(ExpenseCategory::Equipment);
    });

    it('is hidden from customers', function (): void {
        $this->actingAs(User::factory()->customer()->create())->get('/admin/expenses')->assertForbidden();
    });
});
