<?php

declare(strict_types=1);

use App\Modules\Booking\Actions\RescheduleBooking;
use App\Modules\Communication\Actions\SaveEmailTemplate;
use App\Modules\Communication\Actions\SaveNotificationPreferences;
use App\Modules\Communication\Contracts\SmsSender;
use App\Modules\Communication\Enums\Channel;
use App\Modules\Communication\Enums\NotificationType;
use App\Modules\Communication\Filament\Resources\EmailTemplateResource\Pages\ManageEmailTemplates;
use App\Modules\Communication\Infrastructure\SmsChannel;
use App\Modules\Communication\Livewire\NotificationPreferencesForm;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Notifications\CustomerNotification;
use App\Modules\Communication\Support\Preferences;
use App\Modules\Identity\Enums\Locale;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Actions\AdjustCredits;
use App\Modules\Membership\Actions\WarnExpiringMemberships;
use App\Modules\Scheduling\Actions\CancelSession;
use App\Shared\Credits;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(fn () => $this->travelTo('2027-03-20 08:00:00'));

function notification(NotificationType $type = NotificationType::BookingReminder): CustomerNotification
{
    return new CustomerNotification($type, ['service' => 'Pilates', 'date' => 'Τρίτη 23/03/2027', 'time' => '18:30', 'instructor' => 'Ελένη', 'reference' => 'AB12', 'reason' => '']);
}

describe('templates (CM-01)', function (): void {
    it('uses the default wording in the customer language', function (Locale $locale, string $subject): void {
        $user = User::factory()->customer()->create(['first_name' => 'Μαρία', 'locale' => $locale]);

        $mail = notification()->toMail($user);

        expect($mail->subject)->toBe($subject)->and($mail->greeting)->toContain('Μαρία');
    })->with([
        [Locale::Greek, 'Υπενθύμιση: Pilates, Τρίτη 23/03/2027 18:30'],
        [Locale::English, 'Reminder: Pilates, Τρίτη 23/03/2027 18:30'],
    ]);

    it('uses the staff wording where filled in, and the default elsewhere', function (): void {
        $template = EmailTemplate::query()->where('type', NotificationType::BookingReminder)->sole();
        app(SaveEmailTemplate::class)->handle($template, ['el' => 'Μην ξεχάσεις το :service στις :time!', 'en' => ''], ['el' => "Γεια :name.\n\nΣε περιμένουμε."]);

        $greek = notification()->toMail(User::factory()->customer()->create(['first_name' => 'Νίκος']));
        $english = notification()->toMail(User::factory()->customer()->create(['locale' => Locale::English]));

        expect($greek->subject)->toBe('Μην ξεχάσεις το Pilates στις 18:30!')
            ->and($greek->introLines)->toBe(['Γεια Νίκος.', 'Σε περιμένουμε.'])
            ->and($english->subject)->toBe('Reminder: Pilates, Τρίτη 23/03/2027 18:30');
    });

    it('lets staff edit templates in the admin', function (): void {
        $this->actingAs(User::factory()->admin()->create());
        $template = EmailTemplate::query()->where('type', NotificationType::BookingConfirmed)->sole();

        Livewire::test(ManageEmailTemplates::class)
            ->assertCanSeeTableRecords(EmailTemplate::all())
            ->callTableAction('edit', $template, ['subject' => ['el' => 'Κλείστηκε: :service'], 'body' => ['el' => '']]);

        expect($template->fresh()?->getTranslation('subject', 'el'))->toBe('Κλείστηκε: :service')
            ->and($template->fresh()?->getTranslations('body'))->toBe([]);
    });
});

describe('preferences and channels (CM-02)', function (): void {
    it('starts from the defaults of spec 8.1', function (): void {
        $prefs = app(Preferences::class);
        $user = User::factory()->customer()->create();

        expect($prefs->channelsFor($user->id, NotificationType::BookingConfirmed))->toBe([Channel::Mail])
            ->and($prefs->channelsFor($user->id, NotificationType::SessionCancelled))->toBe([Channel::Mail, Channel::Sms]);
    });

    it('follows the customer choices', function (): void {
        $user = User::factory()->customer()->create();
        $matrix = app(Preferences::class)->matrix($user->id);
        $matrix['booking_reminder']['mail'] = false;
        $matrix['booking_reminder']['sms'] = true;

        app(SaveNotificationPreferences::class)->handle($user->id, $matrix);

        expect(notification()->via($user))->toBe([SmsChannel::class]);
    });

    it('sends SMS through the adapter, to customers with a phone', function (): void {
        $sent = [];
        app()->instance(SmsSender::class, new class($sent) implements SmsSender
        {
            public function __construct(private array &$sent) {}

            public function send(string $to, string $message): void
            {
                $this->sent[] = [$to, $message];
            }
        });
        $withPhone = User::factory()->customer()->create(['phone' => '+306900000001']);
        $withoutPhone = User::factory()->customer()->create(['phone' => null]);

        app(SmsChannel::class)->send($withPhone, notification(NotificationType::SessionCancelled));
        app(SmsChannel::class)->send($withoutPhone, notification(NotificationType::SessionCancelled));

        expect($sent)->toHaveCount(1)
            ->and($sent[0][0])->toBe('+306900000001')
            ->and($sent[0][1])->toStartWith('Ακυρώθηκε το μάθημα: Pilates');
    });

    it('saves preferences and marketing consent from the profile', function (): void {
        $user = User::factory()->customer()->create(['marketing_consent_at' => null]);

        Livewire::actingAs($user)->test(NotificationPreferencesForm::class)
            ->assertSet('matrix.session_cancelled.sms', true)
            ->set('matrix.booking_confirmed.mail', false)
            ->set('marketing', true)
            ->call('save')
            ->assertSet('status', __('communication.preferences.saved'));

        expect(app(Preferences::class)->channelsFor($user->id, NotificationType::BookingConfirmed))->toBe([])
            ->and($user->fresh()?->marketing_consent_at?->toDateTimeString())->toBe('2027-03-20 08:00:00');

        $this->actingAs($user)->get(route('app.profile'))->assertSeeLivewire(NotificationPreferencesForm::class);
    });
});

describe('notifications from other modules', function (): void {
    beforeEach(fn () => Notification::fake());

    it('confirms a reschedule with the new time', function (): void {
        $session = bookableSession();
        $customer = customerFor($session);
        $booking = book($customer, $session);
        $target = bookableSession(['starts_at' => '2027-03-25 16:30:00', 'ends_at' => '2027-03-25 17:30:00'], $session->service);

        app(RescheduleBooking::class)->handle($booking, $target->id, $customer->id);

        assertNotified($customer, NotificationType::BookingRescheduled, check: fn (CustomerNotification $n): bool => $n->replace['date'] === 'Πέμπτη 25/03/2027');
    });

    it('warns about expiry and low balance', function (): void {
        $customer = User::factory()->customer()->create();
        $membership = soldMembership($customer, ['validity_days' => 5, 'credits' => Credits::of(2)]);
        app(AdjustCredits::class)->handle($membership, Credits::of('-1.5'), 'x', null);

        app(WarnExpiringMemberships::class)->handle();

        assertNotified($customer, NotificationType::MembershipExpiring, check: fn (CustomerNotification $n): bool => $n->replace['ends_on'] === '24/03/2027');
        assertNotified($customer, NotificationType::LowBalance, check: fn (CustomerNotification $n): bool => $n->replace['balance'] === '0,5');
    });

    it('never writes to anonymised accounts', function (): void {
        $session = bookableSession();
        $customer = customerFor($session);
        book($customer, $session);
        Notification::fake();
        $customer->forceFill(['anonymized_at' => now()])->save();

        app(CancelSession::class)->handle($session, 'x');

        Notification::assertNothingSent();
    });
});
