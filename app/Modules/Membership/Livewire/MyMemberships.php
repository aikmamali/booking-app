<?php

declare(strict_types=1);

namespace App\Modules\Membership\Livewire;

use App\Modules\Membership\Contracts\CreditLedger;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * MB-04: the customer's memberships with period, status, balance and ledger history.
 * Upcoming and completed classes are added with the Booking module (step 4).
 */
#[Layout('components.layouts.app')]
final class MyMemberships extends Component
{
    public function render(CreditLedger $ledger): View
    {
        $memberships = $this->memberships();

        return view('livewire.membership.my-memberships', [
            'memberships' => $memberships,
            'balances' => $memberships->mapWithKeys(fn (Membership $m): array => [$m->id => $ledger->balance($m->id)]),
        ])->title(__('membership.mine.title'));
    }

    /**
     * Active memberships first, then the most recent ones.
     *
     * @return Collection<int, Membership>
     */
    private function memberships(): Collection
    {
        return Membership::query()
            ->where('user_id', auth()->id())
            ->whereNot('status', MembershipStatus::Pending)
            ->with(['transactions' => fn ($q) => $q->orderByDesc('id')])
            ->orderByRaw('status = ? desc', [MembershipStatus::Active->value])
            ->orderByDesc('ends_on')
            ->get();
    }
}
