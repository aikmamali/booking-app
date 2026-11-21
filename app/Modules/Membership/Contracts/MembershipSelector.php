<?php

declare(strict_types=1);

namespace App\Modules\Membership\Contracts;

use App\Shared\Credits;

/**
 * Picks the membership that pays for a session (spec 6.2): active, valid on the session
 * date, covering the service, with enough credits; the one that expires first wins (FEFO).
 */
interface MembershipSelector
{
    /**
     * @param  string  $sessionDate  Y-m-d in the business time zone
     */
    public function choose(int $userId, int $serviceId, Credits $cost, string $sessionDate): MembershipChoice;
}
