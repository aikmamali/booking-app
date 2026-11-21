<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\User;
use App\Shared\Clock;

/**
 * CM-02: marketing messages need their own consent, recorded with its time.
 */
final readonly class UpdateMarketingConsent
{
    public function __construct(private Clock $clock) {}

    public function handle(User $user, bool $consent): void
    {
        if ($consent === ($user->marketing_consent_at !== null)) {
            return;
        }

        $user->forceFill(['marketing_consent_at' => $consent ? $this->clock->now() : null])->save();
    }
}
