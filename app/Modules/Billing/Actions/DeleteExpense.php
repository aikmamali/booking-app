<?php

declare(strict_types=1);

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Models\Expense;
use Illuminate\Support\Facades\DB;

/**
 * BL-02: soft delete, so the entry stays in the audit trail. The attachment is kept with
 * it in case the deletion was a mistake.
 */
final readonly class DeleteExpense
{
    public function handle(Expense $expense): void
    {
        DB::transaction(fn () => $expense->delete());
    }
}
