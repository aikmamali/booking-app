<?php

declare(strict_types=1);

namespace App\Modules\Billing\Data;

use App\Modules\Billing\Enums\ExpenseCategory;
use Spatie\LaravelData\Data;

/**
 * BL-02: an expense as entered by staff. The attachment is a path on Expense::DISK.
 */
final class ExpenseData extends Data
{
    public function __construct(
        public ExpenseCategory $category,
        public int $amount_cents,
        public string $spent_on,
        public ?string $note = null,
        public ?string $attachment_path = null,
    ) {}
}
