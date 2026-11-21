<?php

declare(strict_types=1);

namespace App\Modules\Membership\Data;

use App\Shared\Credits;
use Spatie\LaravelData\Data;

final class PackageData extends Data
{
    /**
     * @param  array<string, string|null>  $name  translations keyed by locale
     * @param  list<int>  $service_ids
     */
    public function __construct(
        public array $name,
        public Credits $credits,
        public int $validity_days,
        public int $price_cents,
        public int $vat_rate,
        public bool $is_purchasable_online,
        public bool $is_active,
        public array $service_ids,
    ) {}
}
