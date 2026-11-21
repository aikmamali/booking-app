<?php

declare(strict_types=1);

namespace App\Modules\Membership;

use App\Modules\Membership\Contracts\CreditLedger;
use App\Modules\Membership\Contracts\MembershipSelector;
use App\Modules\Membership\Ledger\DatabaseCreditLedger;
use App\Modules\Membership\Ledger\FefoMembershipSelector;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\Package;
use App\Modules\Membership\Policies\MembershipPolicy;
use App\Modules\Membership\Policies\PackagePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class MembershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DatabaseCreditLedger::class);
        $this->app->bind(CreditLedger::class, DatabaseCreditLedger::class);
        $this->app->bind(MembershipSelector::class, FefoMembershipSelector::class);
    }

    public function boot(): void
    {
        Gate::policy(Package::class, PackagePolicy::class);
        Gate::policy(Membership::class, MembershipPolicy::class);
    }
}
