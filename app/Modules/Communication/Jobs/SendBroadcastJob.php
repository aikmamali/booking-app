<?php

declare(strict_types=1);

namespace App\Modules\Communication\Jobs;

use App\Modules\Communication\Actions\SendBroadcastBatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * CM-03: sends one batch, then queues itself for the next one, so a long list never
 * blocks the queue worker.
 */
final class SendBroadcastJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $broadcastId) {}

    public function handle(SendBroadcastBatch $batch): void
    {
        if ($batch->handle($this->broadcastId)) {
            self::dispatch($this->broadcastId);
        }
    }
}
