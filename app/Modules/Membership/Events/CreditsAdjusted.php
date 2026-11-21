<?php

declare(strict_types=1);

namespace App\Modules\Membership\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Staff corrected credits by hand (MB-07).
 */
final readonly class CreditsAdjusted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $membershipId, public string $amount, public string $reason) {}
}
