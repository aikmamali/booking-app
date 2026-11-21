<?php

declare(strict_types=1);

use App\Modules\Communication\Enums\NotificationType;
use App\Modules\Communication\Notifications\CustomerNotification;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/*
| Feature tests run against MySQL (row locks matter), never SQLite.
*/
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Concurrency tests fork processes with their own connections, so data must be committed:
// no wrapping transaction, a fresh schema per test instead.
pest()->extend(TestCase::class)
    ->use(DatabaseMigrations::class)
    ->in('Concurrency');

// Booking rules are pure, but their models need the framework booted (casts, dates).
pest()->extend(TestCase::class)->in('Unit/Booking');

require_once __DIR__.'/Feature/Scheduling/helpers.php';
require_once __DIR__.'/Feature/Membership/helpers.php';
require_once __DIR__.'/Feature/Booking/helpers.php';

/**
 * Asserts a customer notification of the given type was sent to the user, and how often.
 */
function assertNotified(User $user, NotificationType $type, int $times = 1, ?Closure $check = null): void
{
    $sent = Notification::sent($user, CustomerNotification::class)
        ->filter(fn (CustomerNotification $n): bool => $n->type === $type && (! $check instanceof Closure || $check($n)));

    expect($sent)->toHaveCount($times, "Expected {$times} {$type->value} notification(s) to user {$user->id}.");
}
