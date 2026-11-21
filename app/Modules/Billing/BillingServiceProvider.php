<?php

declare(strict_types=1);

namespace App\Modules\Billing;

use App\Modules\Billing\Listeners\RecordPackageSale;
use App\Modules\Billing\Models\Payment;
use App\Modules\Billing\Policies\PaymentPolicy;
use App\Modules\Membership\Events\MembershipSold;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class BillingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Payment::class, PaymentPolicy::class);

        Event::listen(MembershipSold::class, RecordPackageSale::class);
    }
}
