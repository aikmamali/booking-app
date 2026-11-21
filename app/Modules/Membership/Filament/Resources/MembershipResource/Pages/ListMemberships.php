<?php

declare(strict_types=1);

namespace App\Modules\Membership\Filament\Resources\MembershipResource\Pages;

use App\Modules\Membership\Filament\Resources\MembershipResource;
use Filament\Resources\Pages\ListRecords;

final class ListMemberships extends ListRecords
{
    protected static string $resource = MembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [MembershipResource::sellAction()];
    }
}
