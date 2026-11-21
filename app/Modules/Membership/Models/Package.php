<?php

declare(strict_types=1);

namespace App\Modules\Membership\Models;

use App\Modules\Catalog\Models\Service;
use App\Shared\Casts\CreditsCast;
use App\Shared\Credits;
use App\Shared\Money;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * A product for sale (MB-01): "10 classes, valid 3 months, 50 €".
 *
 * @property int $id
 * @property string $name
 * @property Credits $credits
 * @property int $validity_days
 * @property int $price_cents
 * @property int $vat_rate
 * @property bool $is_purchasable_online
 * @property bool $is_active
 * @property int $position
 */
#[UseFactory(PackageFactory::class)]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['name'];

    /** @var list<string> */
    protected $fillable = [
        'name', 'credits', 'validity_days', 'price_cents', 'vat_rate', 'is_purchasable_online', 'is_active', 'position',
    ];

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function price(): Money
    {
        return Money::ofCents($this->price_cents);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credits' => CreditsCast::class,
            'is_purchasable_online' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
