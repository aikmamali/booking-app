<?php

declare(strict_types=1);

namespace App\Modules\Membership\Filament\Resources\MembershipResource\Pages;

use App\Modules\Membership\Filament\Resources\MembershipResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewMembership extends ViewRecord
{
    protected static string $resource = MembershipResource::class;
}
