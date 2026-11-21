<?php

declare(strict_types=1);

namespace App\Modules\Membership\Jobs;

use App\Modules\Membership\Actions\WarnExpiringMemberships;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class WarnExpiringMembershipsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(WarnExpiringMemberships $action): void
    {
        $action->handle();
    }
}
