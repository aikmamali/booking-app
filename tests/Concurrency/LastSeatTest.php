<?php

declare(strict_types=1);

use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\Booking;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Enums\TransactionType;
use App\Modules\Membership\Models\CreditTransaction;
use App\Modules\Scheduling\Models\ClassSession;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

/*
| Spec 10.3: two customers book the last seat at the same moment → exactly one succeeds.
| Each customer runs in its own forked process with its own MySQL connection. The first
| process pauses for 300 ms right after counting the taken seats, the exact window in which
| an unlocked implementation would let the second process see a free seat too. Removing
| lockForUpdate() from BookSession makes this test fail with two bookings.
*/
it('sells the last seat exactly once to two simultaneous customers', function (): void {
    $this->travelTo('2027-03-20 08:00:00');
    $session = bookableSession(['capacity' => 1]);
    $customers = [customerFor($session), customerFor($session)];
    $results = sys_get_temp_dir().'/last-seat-'.getmypid();
    @mkdir($results);

    DB::disconnect();
    $startAt = microtime(true) + 0.5;
    $pids = [];

    foreach ($customers as $i => $customer) {
        $pid = pcntl_fork();

        if ($pid === 0) {
            runChild($i, $customer->id, $session->id, $startAt, $results);
        }

        $pids[] = $pid;
    }

    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
    }

    DB::reconnect();
    $outcomes = array_map(fn (int $i): string => (string) @file_get_contents("{$results}/{$i}"), array_keys($customers));
    sort($outcomes);
    array_map(unlink(...), glob("{$results}/*") ?: []);
    rmdir($results);

    expect($outcomes)->toBe(['booked', 'rejected:booking.full'])
        ->and(Booking::query()->active()->where('class_session_id', $session->id)->count())->toBe(1)
        ->and(CreditTransaction::query()->where('type', TransactionType::Debit)->count())->toBe(1);
})->skip(! function_exists('pcntl_fork'), 'needs the pcntl extension');

/**
 * Child process: wait for the common start, book, record the outcome, die without
 * running the parent's shutdown handlers.
 */
function runChild(int $index, int $userId, int $sessionId, float $startAt, string $results): never
{
    DB::reconnect();

    if ($index === 0) {
        DB::listen(function (QueryExecuted $query): void {
            if (str_contains($query->sql, 'count(*)') && str_contains($query->sql, '`class_session_id` = ?')) {
                usleep(300_000); // seats counted, not yet booked: the race window
            }
        });
    } else {
        usleep(100_000); // start just after the first one has taken the lock
    }

    while (microtime(true) < $startAt) {
        usleep(1_000);
    }

    try {
        book(User::query()->findOrFail($userId), ClassSession::query()->findOrFail($sessionId));
        $outcome = 'booked';
    } catch (BookingRejected $e) {
        $outcome = 'rejected:'.$e->key;
    } catch (Throwable $e) {
        $outcome = 'error:'.$e::class.':'.$e->getMessage();
    }

    file_put_contents("{$results}/{$index}", $outcome);
    posix_kill(posix_getpid(), SIGKILL);
    exit(1);
}
