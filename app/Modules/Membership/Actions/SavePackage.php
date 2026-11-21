<?php

declare(strict_types=1);

namespace App\Modules\Membership\Actions;

use App\Modules\Membership\Data\PackageData;
use App\Modules\Membership\Exceptions\LedgerRejected;
use App\Modules\Membership\Models\Package;
use Illuminate\Support\Facades\DB;

/**
 * MB-01. Changing a package never changes memberships already sold: they keep a snapshot.
 */
final class SavePackage
{
    public function handle(?Package $package, PackageData $data): Package
    {
        if (! $data->credits->isPositive() || $data->service_ids === []) {
            throw LedgerRejected::because('membership.errors.package_incomplete');
        }

        return DB::transaction(function () use ($package, $data): Package {
            $package ??= new Package(['position' => (int) Package::query()->max('position') + 1]);
            $package->fill([
                'credits' => $data->credits,
                'validity_days' => $data->validity_days,
                'price_cents' => $data->price_cents,
                'vat_rate' => $data->vat_rate,
                'is_purchasable_online' => $data->is_purchasable_online,
                'is_active' => $data->is_active,
            ]);
            $package->setTranslations('name', array_filter($data->name, filled(...)));
            $package->save();
            $package->services()->sync($data->service_ids);

            return $package;
        });
    }
}
