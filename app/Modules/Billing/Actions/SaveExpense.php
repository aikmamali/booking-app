<?php

declare(strict_types=1);

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Data\ExpenseData;
use App\Modules\Billing\Exceptions\PaymentRejected;
use App\Modules\Billing\Models\Expense;
use App\Shared\BusinessTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * BL-02: records a new expense or corrects one. A replaced attachment is deleted from
 * the disk once the change is saved.
 */
final readonly class SaveExpense
{
    public function __construct(private BusinessTime $time) {}

    public function handle(ExpenseData $data, ?int $actorId, ?Expense $expense = null): Expense
    {
        $this->validate($data);

        $oldAttachment = $expense?->attachment_path;

        $saved = DB::transaction(function () use ($data, $actorId, $expense): Expense {
            $expense ??= new Expense(['recorded_by' => $actorId]);
            $expense->fill([
                'category' => $data->category,
                'amount_cents' => $data->amount_cents,
                'spent_on' => $data->spent_on,
                'note' => $data->note === null || trim($data->note) === '' ? null : trim($data->note),
                'attachment_path' => $data->attachment_path,
            ])->save();

            return $expense;
        });

        if ($oldAttachment !== null && $oldAttachment !== $saved->attachment_path) {
            Storage::disk(Expense::DISK)->delete($oldAttachment);
        }

        return $saved;
    }

    private function validate(ExpenseData $data): void
    {
        if ($data->amount_cents <= 0) {
            throw PaymentRejected::because('billing.errors.amount');
        }

        if ($data->spent_on > $this->time->today()) {
            throw PaymentRejected::because('billing.errors.expense_in_future');
        }
    }
}
