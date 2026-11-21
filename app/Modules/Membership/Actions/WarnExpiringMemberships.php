<?php

declare(strict_types=1);

namespace App\Modules\Membership\Actions;

use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Membership\Contracts\CreditLedger;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Events\LowCreditBalance;
use App\Modules\Membership\Events\MembershipExpiringSoon;
use App\Modules\Membership\Models\Membership;
use App\Shared\BusinessTime;
use App\Shared\Clock;
use App\Shared\Credits;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * MB-05: flags memberships that end soon or run low on credits, once per membership each.
 * Communication turns the events into notifications (step 6).
 */
final readonly class WarnExpiringMemberships
{
    public function __construct(
        private BookingPolicySettings $settings,
        private CreditLedger $ledger,
        private BusinessTime $time,
        private Clock $clock,
    ) {}

    /**
     * @return array{expiring: int, low_balance: int}
     */
    public function handle(): array
    {
        return DB::transaction(fn (): array => [
            'expiring' => $this->warnExpiring(),
            'low_balance' => $this->warnLowBalance(),
        ]);
    }

    private function warnExpiring(): int
    {
        $today = $this->time->today();
        $until = CarbonImmutable::parse($today)->addDays($this->settings->membership_expiry_warning_days)->format('Y-m-d');

        $memberships = Membership::query()
            ->where('status', MembershipStatus::Active)
            ->whereNull('expiry_warned_at')
            ->whereDate('ends_on', '>=', $today)
            ->whereDate('ends_on', '<=', $until)
            ->get();

        foreach ($memberships as $membership) {
            $membership->forceFill(['expiry_warned_at' => $this->clock->now()])->save();
            event(new MembershipExpiringSoon($membership->id));
        }

        return $memberships->count();
    }

    private function warnLowBalance(): int
    {
        $threshold = Credits::whole($this->settings->low_balance_threshold);
        $count = 0;

        $memberships = Membership::query()
            ->where('status', MembershipStatus::Active)
            ->whereNull('low_balance_warned_at')
            ->get();

        foreach ($memberships as $membership) {
            if ($threshold->greaterThanOrEqual($this->ledger->balance($membership->id))) {
                $membership->forceFill(['low_balance_warned_at' => $this->clock->now()])->save();
                event(new LowCreditBalance($membership->id));
                $count++;
            }
        }

        return $count;
    }
}
