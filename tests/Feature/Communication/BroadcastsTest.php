<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Service;
use App\Modules\Communication\Actions\QueueBroadcast;
use App\Modules\Communication\Actions\SendBroadcastBatch;
use App\Modules\Communication\Data\BroadcastAudience;
use App\Modules\Communication\Data\BroadcastData;
use App\Modules\Communication\Enums\BroadcastStatus;
use App\Modules\Communication\Enums\Channel;
use App\Modules\Communication\Enums\NotificationType;
use App\Modules\Communication\Exceptions\BroadcastRejected;
use App\Modules\Communication\Filament\Resources\BroadcastResource\Pages\ListBroadcasts;
use App\Modules\Communication\Jobs\SendBroadcastJob;
use App\Modules\Communication\Models\Broadcast;
use App\Modules\Communication\Notifications\BroadcastMessage;
use App\Modules\Communication\Notifications\CustomerNotification;
use App\Modules\Communication\Notifications\SmsMessage;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo('2027-03-20 08:00:00');
    Notification::fake();
});

function consenting(array $attributes = []): User
{
    return User::factory()->customer()->create(['marketing_consent_at' => now(), ...$attributes]);
}

function sendToAudience(BroadcastAudience $audience = new BroadcastAudience, array $channels = [Channel::Mail]): Broadcast
{
    return app(QueueBroadcast::class)->handle(new BroadcastData('Νέο πρόγραμμα', "Από Δευτέρα.\n\nΚαλή εβδομάδα!", $channels, $audience), null);
}

describe('audience', function (): void {
    it('reaches only active customers who agreed to receive updates', function (): void {
        $yes = consenting();
        User::factory()->customer()->create(['marketing_consent_at' => null]);
        consenting(['anonymized_at' => now()]);
        User::factory()->admin()->create(['marketing_consent_at' => now()]);

        $b = sendToAudience();

        expect($b->recipients_count)->toBe(1)
            ->and($b->recipients()->pluck('user_id')->all())->toBe([$yes->id]);
        Notification::assertSentTo($yes, BroadcastMessage::class);
        Notification::assertCount(1);
    });

    it('filters by active membership, expiry window and service', function (): void {
        $yoga = Service::factory()->create();
        $pilates = Service::factory()->create();
        $endsSoon = consenting();
        soldMembership($endsSoon, ['validity_days' => 5], '2027-03-20', $yoga); // ends 24/3
        $endsLater = consenting();
        soldMembership($endsLater, ['validity_days' => 90], '2027-03-20', $pilates);
        consenting(); // no membership

        $ids = fn (BroadcastAudience $a): array => sendToAudience($a)->recipients()->orderBy('user_id')->pluck('user_id')->all();

        expect($ids(new BroadcastAudience(active_membership: true)))->toBe([$endsSoon->id, $endsLater->id])
            ->and($ids(new BroadcastAudience(expiring_within_days: 7)))->toBe([$endsSoon->id])
            ->and($ids(new BroadcastAudience(service_id: $pilates->id)))->toBe([$endsLater->id]);
    });

    it('refuses an audience with nobody in it, empty text or no channel', function (): void {
        expect(fn (): Broadcast => sendToAudience())->toThrow(BroadcastRejected::class, 'communication.broadcasts.errors.no_recipients');

        consenting();
        expect(fn (): Broadcast => sendToAudience(channels: []))->toThrow(BroadcastRejected::class, 'communication.broadcasts.errors.channels')
            ->and(fn () => app(QueueBroadcast::class)->handle(new BroadcastData(' ', 'x', [Channel::Mail], new BroadcastAudience), null))
            ->toThrow(BroadcastRejected::class, 'communication.broadcasts.errors.empty');
    });
});

describe('sending', function (): void {
    it('queues the sending after the broadcast is saved', function (): void {
        Queue::fake();
        consenting();

        $b = sendToAudience();

        Queue::assertPushed(SendBroadcastJob::class, fn (SendBroadcastJob $job): bool => $job->broadcastId === $b->id);
        expect($b->status)->toBe(BroadcastStatus::Queued);
    });

    it('sends in batches, marks each recipient and completes', function (): void {
        Queue::fake();
        User::factory()->customer()->count(SendBroadcastBatch::BATCH + 5)->create(['marketing_consent_at' => now()]);
        $b = sendToAudience();
        $batch = app(SendBroadcastBatch::class);

        expect($batch->handle($b->id))->toBeTrue()
            ->and($b->refresh()->sent_count)->toBe(SendBroadcastBatch::BATCH)
            ->and($batch->handle($b->id))->toBeFalse()
            ->and($b->refresh()->sent_count)->toBe(SendBroadcastBatch::BATCH + 5)
            ->and($b->status)->toBe(BroadcastStatus::Completed)
            ->and($batch->handle($b->id))->toBeFalse();

        Notification::assertSentTimes(BroadcastMessage::class, SendBroadcastBatch::BATCH + 5);
    });

    it('skips customers who withdrew consent after queueing', function (): void {
        Queue::fake();
        $customer = consenting();
        $b = sendToAudience();
        $customer->forceFill(['marketing_consent_at' => null])->save();

        app(SendBroadcastBatch::class)->handle($b->id);

        Notification::assertNothingSent();
        expect($b->refresh()->status)->toBe(BroadcastStatus::Completed);
    });

    it('sends SMS only to customers with a phone, cut to one message', function (): void {
        $message = new BroadcastMessage('Θέμα', str_repeat('λέξη ', 100), [Channel::Mail->value, Channel::Sms->value]);
        $with = consenting(['phone' => '+306900000001']);
        $without = consenting(['phone' => null]);

        expect($message->via($with))->toHaveCount(2)
            ->and($message->via($without))->toBe(['mail'])
            ->and(mb_strlen($message->toSms($with)))->toBeLessThanOrEqual(155);
    });

    it('ends emails with a way to opt out', function (): void {
        $mail = (new BroadcastMessage('Θέμα', "Α\n\nΒ", ['mail']))->toMail(consenting());

        expect($mail->introLines)->toBe(['Α', 'Β', __('communication.broadcasts.unsubscribe', ['url' => route('app.profile')])]);
    });
});

describe('admin', function (): void {
    it('shows the audience size and queues the message', function (): void {
        Queue::fake();
        $this->actingAs(User::factory()->admin()->create());
        consenting();
        consenting();

        Livewire::test(ListBroadcasts::class)
            ->mountAction('send')
            ->assertSee('2 πελάτες')
            ->setActionData(['subject' => 'Γεια', 'body' => 'Κείμενο', 'channels' => ['mail']])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        expect(Broadcast::query()->sole()->recipients_count)->toBe(2);
    });

    it('is not available to instructors', function (): void {
        $this->actingAs(User::factory()->instructor()->create())->get('/admin/broadcasts')->assertForbidden();
    });
});

it('keeps transactional SMS within one message too', function (): void {
    $notification = new CustomerNotification(
        NotificationType::SessionCancelled,
        ['service' => str_repeat('Πολύ μεγάλο όνομα μαθήματος ', 10), 'date' => '23/03/2027', 'time' => '18:30', 'reason' => 'x'],
    );

    expect(mb_strlen($notification->toSms(consenting())))->toBeLessThanOrEqual(SmsMessage::MAX_LENGTH);
});
