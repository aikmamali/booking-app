<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Modules\Billing\Enums\ExpenseCategory;
use App\Modules\Identity\Models\User;
use App\Shared\Money;
use Carbon\CarbonImmutable;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * BL-02. Attachments (receipts, invoices) live on the private disk.
 *
 * @property int $id
 * @property string $public_id
 * @property ExpenseCategory $category
 * @property int $amount_cents
 * @property CarbonImmutable $spent_on
 * @property ?string $note
 * @property ?string $attachment_path
 * @property ?int $recorded_by
 * @property-read ?User $recorder
 */
#[UseFactory(ExpenseFactory::class)]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    public const string DISK = 'local';

    public const string DIRECTORY = 'expenses';

    /** @var list<string> */
    protected $fillable = ['category', 'amount_cents', 'spent_on', 'note', 'attachment_path', 'recorded_by'];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function amount(): Money
    {
        return Money::ofCents($this->amount_cents);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
            'spent_on' => 'immutable_date',
        ];
    }
}
