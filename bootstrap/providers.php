<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Modules\Identity\IdentityServiceProvider::class,
    App\Modules\Catalog\CatalogServiceProvider::class,
    App\Modules\Scheduling\SchedulingServiceProvider::class,
    App\Modules\Booking\BookingServiceProvider::class,
    App\Modules\Membership\MembershipServiceProvider::class,
    App\Modules\Billing\BillingServiceProvider::class,
    App\Modules\Communication\CommunicationServiceProvider::class,
    App\Modules\Reporting\ReportingServiceProvider::class,
];
