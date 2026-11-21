<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Data;

use App\Shared\Credits;
use Spatie\LaravelData\Data;

final class ServiceData extends Data
{
    /**
     * @param  array<string, string>  $name  translations keyed by locale
     * @param  array<string, string|null>  $description
     */
    public function __construct(
        public int $category_id,
        public array $name,
        public array $description,
        public ?string $image_path,
        public int $duration_minutes,
        public int $default_capacity,
        public Credits $credit_cost,
        public ?int $drop_in_price_cents,
        public bool $is_active,
        public PolicyOverrides $policy_overrides,
    ) {}
}
