<?php

declare(strict_types=1);

namespace App\Modules\Communication\Actions;

use App\Modules\Communication\Data\BroadcastData;
use App\Modules\Communication\Enums\BroadcastStatus;
use App\Modules\Communication\Enums\Channel;
use App\Modules\Communication\Exceptions\BroadcastRejected;
use App\Modules\Communication\Jobs\SendBroadcastJob;
use App\Modules\Communication\Models\Broadcast;
use App\Modules\Communication\Support\BroadcastRecipients;
use Illuminate\Support\Facades\DB;

/**
 * CM-03: fixes the recipients and queues the sending. Sending runs in the background
 * in batches, so the admin screen returns at once.
 */
final readonly class QueueBroadcast
{
    public function __construct(private BroadcastRecipients $recipients) {}

    public function handle(BroadcastData $data, ?int $actorId): Broadcast
    {
        $subject = trim($data->subject);
        $body = trim($data->body);

        if ($subject === '' || $body === '') {
            throw BroadcastRejected::because('communication.broadcasts.errors.empty');
        }

        if ($data->channels === []) {
            throw BroadcastRejected::because('communication.broadcasts.errors.channels');
        }

        $ids = $this->recipients->ids($data->audience);

        if ($ids === []) {
            throw BroadcastRejected::because('communication.broadcasts.errors.no_recipients');
        }

        return DB::transaction(function () use ($data, $actorId, $subject, $body, $ids): Broadcast {
            $broadcast = Broadcast::query()->create([
                'subject' => $subject,
                'body' => $body,
                'channels' => array_values(array_unique(array_map(fn (Channel $c): string => $c->value, $data->channels))),
                'audience' => $data->audience->toArray(),
                'status' => BroadcastStatus::Queued,
                'recipients_count' => count($ids),
                'created_by' => $actorId,
            ]);

            foreach (array_chunk($ids, 500) as $chunk) {
                $broadcast->recipients()->insert(array_map(
                    fn (int $id): array => ['broadcast_id' => $broadcast->id, 'user_id' => $id],
                    $chunk,
                ));
            }

            SendBroadcastJob::dispatch($broadcast->id)->afterCommit();

            return $broadcast;
        });
    }
}
