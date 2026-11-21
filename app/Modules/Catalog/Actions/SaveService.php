<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Data\ServiceData;
use App\Modules\Catalog\Models\Service;

/**
 * SC-01: creates or updates a service. New services go to the end of the display order.
 */
final class SaveService
{
    public function handle(?Service $service, ServiceData $data): Service
    {
        $service ??= new Service(['position' => (int) Service::query()->max('position') + 1]);

        $service->fill([
            'category_id' => $data->category_id,
            'image_path' => $data->image_path,
            'duration_minutes' => $data->duration_minutes,
            'default_capacity' => $data->default_capacity,
            'credit_cost' => $data->credit_cost,
            'drop_in_price_cents' => $data->drop_in_price_cents,
            'is_active' => $data->is_active,
            'policy_overrides' => $data->policy_overrides->isEmpty() ? null : $data->policy_overrides,
        ]);

        $service->setTranslations('name', $this->filled($data->name));
        $service->setTranslations('description', $this->filled($data->description));
        $service->save();

        return $service;
    }

    /**
     * @param  array<string, string|null>  $translations
     * @return array<string, string>
     */
    private function filled(array $translations): array
    {
        return array_filter($translations, filled(...));
    }
}
