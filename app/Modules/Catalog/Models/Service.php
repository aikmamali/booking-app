<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Data\PolicyOverrides;
use App\Shared\Casts\CreditsCast;
use App\Shared\Credits;
use App\Shared\Money;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * A kind of class (SC-01). Public contract of the Catalog module: other modules may read it.
 *
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property ?string $description
 * @property ?string $image_path
 * @property int $duration_minutes
 * @property int $default_capacity
 * @property Credits $credit_cost
 * @property ?int $drop_in_price_cents
 * @property bool $is_active
 * @property int $position
 * @property ?PolicyOverrides $policy_overrides
 */
#[UseFactory(ServiceFactory::class)]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'category_id', 'name', 'description', 'image_path', 'duration_minutes', 'default_capacity',
        'credit_cost', 'drop_in_price_cents', 'is_active', 'position', 'policy_overrides',
    ];

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function dropInPrice(): ?Money
    {
        return $this->drop_in_price_cents === null ? null : Money::ofCents($this->drop_in_price_cents);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credit_cost' => CreditsCast::class,
            'is_active' => 'boolean',
            'policy_overrides' => PolicyOverrides::class,
        ];
    }
}
