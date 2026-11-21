<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\ServiceCategory;

final class SaveServiceCategory
{
    /**
     * @param  array<string, string|null>  $name  translations keyed by locale
     */
    public function handle(?ServiceCategory $category, array $name): ServiceCategory
    {
        $category ??= new ServiceCategory(['position' => (int) ServiceCategory::query()->max('position') + 1]);

        $category->setTranslations('name', array_filter($name, filled(...)));
        $category->save();

        return $category;
    }
}
