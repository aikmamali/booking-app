<?php

declare(strict_types=1);

namespace App\Modules\Catalog;

use App\Modules\Catalog\Models\Room;
use App\Modules\Catalog\Models\Service;
use App\Modules\Catalog\Models\ServiceCategory;
use App\Modules\Catalog\Policies\RoomPolicy;
use App\Modules\Catalog\Policies\ServiceCategoryPolicy;
use App\Modules\Catalog\Policies\ServicePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class CatalogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(ServiceCategory::class, ServiceCategoryPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Room::class, RoomPolicy::class);
    }
}
