<?php

declare(strict_types=1);

namespace App\Modules\Communication\Actions;

use App\Modules\Communication\Enums\BroadcastStatus;
use App\Modules\Communication\Models\Broadcast;
use App\Modules\Communication\Models\BroadcastRecipient;
use App\Modules\Communication\Notifications\BroadcastMessage;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * CM-03: sends to the next batch of recipients not yet reached and reports whether more
 * remain. Idempotent: each recipient is marked as sent right after their message.
 */
final readonly class SendBroadcastBatch
{
    public const int BATCH = 100;

    public function __construct(private Clock $clock) {}

    public function handle(int $broadcastId): bool
    {
        $broadcast = Broadcast::query()->findOrFail($broadcastId);

        if ($broadcast->status === BroadcastStatus::Completed) {
            return false;
        }

        $batch = $broadcast->recipients()->with('user')->whereNull('sent_at')->orderBy('id')->limit(self::BATCH)->get();

        foreach ($batch as $recipient) {
            $this->send($broadcast, $recipient);
        }

        if ($broadcast->recipients()->whereNull('sent_at')->exists()) {
            return true;
        }

        DB::transaction(fn () => $broadcast->forceFill(['status' => BroadcastStatus::Completed, 'completed_at' => $this->clock->now()])->save());

        return false;
    }

    private function send(Broadcast $broadcast, BroadcastRecipient $recipient): void
    {
        // Consent may have been withdrawn after queueing.
        if ($recipient->user->marketing_consent_at !== null && $recipient->user->anonymized_at === null) {
            $recipient->user->notifyNow(new BroadcastMessage($broadcast->subject, $broadcast->body, $broadcast->channels));
        }

        DB::transaction(function () use ($broadcast, $recipient): void {
            $recipient->forceFill(['sent_at' => $this->clock->now()])->save();
            $broadcast->increment('sent_count');
        });
    }
}
