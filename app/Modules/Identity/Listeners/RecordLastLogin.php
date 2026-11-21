<?php

declare(strict_types=1);

namespace App\Modules\Identity\Listeners;

use App\Modules\Identity\Models\User;
use App\Shared\Clock;
use Illuminate\Auth\Events\Login;

final readonly class RecordLastLogin
{
    public function __construct(private Clock $clock) {}

    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $event->user->forceFill(['last_login_at' => $this->clock->now()])->saveQuietly();
        }
    }
}
