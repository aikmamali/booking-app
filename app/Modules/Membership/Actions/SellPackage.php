<?php

declare(strict_types=1);

namespace App\Modules\Membership\Actions;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Data\SaleData;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Enums\TransactionType;
use App\Modules\Membership\Events\MembershipSold;
use App\Modules\Membership\Exceptions\LedgerRejected;
use App\Modules\Membership\Ledger\DatabaseCreditLedger;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\Package;
use App\Shared\BusinessTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * MB-02: staff sell a package. Creates the membership with a snapshot of the package
 * terms and the `purchase` ledger entry. Billing records the payment from MembershipSold.
 */
final readonly class SellPackage
{
    public function __construct(
        private DatabaseCreditLedger $ledger,
        private BusinessTime $time,
    ) {}

    public function handle(SaleData $data, ?int $actorId = null): Membership
    {
        $package = Package::query()->with('services')->findOrFail($data->package_id);
        $this->guard($data, $package);

        return DB::transaction(function () use ($data, $package, $actorId): Membership {
            $membership = Membership::query()->create($this->snapshot($data, $package, $actorId));

            $this->ledger->record($membership->id, TransactionType::Purchase, $package->credits, actorId: $actorId);

            event(new MembershipSold($membership->id, $data->user_id, $package->price_cents, $data->payment_method));

            return $membership;
        });
    }

    private function guard(SaleData $data, Package $package): void
    {
        $customer = User::query()->find($data->user_id);

        if ($customer === null || $customer->isAnonymized() || ! $customer->hasRole(Role::Customer->value)) {
            throw LedgerRejected::because('membership.errors.not_a_customer');
        }

        if (! $package->is_active) {
            throw LedgerRejected::because('membership.errors.package_inactive');
        }

        if ($data->starts_on < $this->time->today()) {
            throw LedgerRejected::because('membership.errors.starts_in_past');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(SaleData $data, Package $package, ?int $actorId): array
    {
        $startsOn = CarbonImmutable::parse($data->starts_on);

        return [
            'user_id' => $data->user_id,
            'package_id' => $package->id,
            'starts_on' => $startsOn,
            'ends_on' => $startsOn->addDays($package->validity_days - 1),
            'status' => MembershipStatus::Active,
            'package_name' => $package->getTranslations('name'),
            'credits' => $package->credits,
            'price_cents' => $package->price_cents,
            'vat_rate' => $package->vat_rate,
            'validity_days' => $package->validity_days,
            'service_ids' => $package->services->modelKeys(),
            'sold_by' => $actorId,
        ];
    }
}
