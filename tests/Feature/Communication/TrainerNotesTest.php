<?php

declare(strict_types=1);

use App\Modules\Scheduling\Models\ClassSession;
use App\Modules\Booking\Actions\CancelBooking;
use App\Modules\Booking\Contracts\InstructorRoster;
use App\Modules\Booking\Data\CancellationRequest;
use App\Modules\Communication\Actions\DeleteNote;
use App\Modules\Communication\Actions\SaveNote;
use App\Modules\Communication\Data\NoteData;
use App\Modules\Communication\Exceptions\NoteRejected;
use App\Modules\Communication\Filament\Pages\MyClasses;
use App\Modules\Communication\Filament\Resources\NoteResource\Pages\ManageNotes;
use App\Modules\Communication\Models\Note;
use App\Modules\Identity\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo('2027-03-20 08:00:00');
    $this->instructor = User::factory()->instructor()->create(['last_name' => 'Εκπαιδευτού']);
    $this->other = User::factory()->instructor()->create();
    $this->session = bookableSession(['instructor_id' => $this->instructor->id]);
    $this->customer = customerFor($this->session);
    $this->booking = book($this->customer, $this->session);
});

function note(User $author, User $customer, array $overrides = [], ?Note $note = null): Note
{
    return app(SaveNote::class)->handle(new NoteData(...[
        'user_id' => $customer->id,
        'body' => ' Καλή στάση σώματος. ',
        ...$overrides,
    ]), $author, $note);
}

/**
 * The same service the next day, so the customer's membership covers it and the daily
 * limit is not hit.
 */
function otherDaySession(User $instructor, ClassSession $like): ClassSession
{
    return bookableSession([
        'instructor_id' => $instructor->id,
        'starts_at' => '2027-03-24 16:30:00',
        'ends_at' => '2027-03-24 17:30:00',
    ], $like->service);
}

describe('roster', function (): void {
    it('knows which customers an instructor teaches', function (): void {
        $roster = app(InstructorRoster::class);

        expect($roster->customerIds($this->instructor->id))->toBe([$this->customer->id])
            ->and($roster->teaches($this->instructor->id, $this->customer->id))->toBeTrue()
            ->and($roster->teaches($this->other->id, $this->customer->id))->toBeFalse()
            ->and($roster->bookingChoices($this->customer->id, $this->instructor->id))->toHaveKey($this->booking->id)
            ->and($roster->attendees($this->session->id))->toHaveCount(1);
    });

    it('ignores cancelled bookings', function (): void {
        app(CancelBooking::class)->handle($this->booking, new CancellationRequest(actor_id: $this->customer->id));

        expect(app(InstructorRoster::class)->customerIds($this->instructor->id))->toBe([])
            ->and(app(InstructorRoster::class)->attendees($this->session->id))->toBe([]);
    });
});

describe('writing notes', function (): void {
    it('lets an instructor note a customer of their class, optionally on the booking', function (): void {
        $n = note($this->instructor, $this->customer, ['booking_id' => $this->booking->id, 'visible_to_customer' => true]);

        expect($n->body)->toBe('Καλή στάση σώματος.')
            ->and($n->author_id)->toBe($this->instructor->id)
            ->and($n->booking_id)->toBe($this->booking->id)
            ->and($n->visible_to_customer)->toBeTrue();
    });

    it('refuses customers of other instructors', function (): void {
        note($this->other, $this->customer);
    })->throws(NoteRejected::class, 'communication.notes.errors.not_your_customer');

    it('refuses a booking of someone else or of another instructor', function (): void {
        $foreignSession = otherDaySession($this->other, $this->session);
        $foreign = book($this->customer, $foreignSession);

        expect(fn (): Note => note($this->instructor, $this->customer, ['booking_id' => $foreign->id]))
            ->toThrow(NoteRejected::class, 'communication.notes.errors.booking');
    });

    it('refuses an empty note', function (): void {
        note($this->instructor, $this->customer, ['body' => '   ']);
    })->throws(NoteRejected::class, 'communication.notes.errors.body');

    it('lets only the author or an admin change a note', function (): void {
        $n = note($this->instructor, $this->customer);
        $colleagueSession = otherDaySession($this->other, $this->session);
        book($this->customer, $colleagueSession);

        expect(fn (): Note => note($this->other, $this->customer, ['body' => 'x'], $n))->toThrow(NoteRejected::class, 'communication.notes.errors.not_yours')
            ->and(fn () => app(DeleteNote::class)->handle($n, $this->other))->toThrow(NoteRejected::class);

        $admin = User::factory()->admin()->create();
        expect(note($admin, $this->customer, ['body' => 'Διόρθωση'], $n)->body)->toBe('Διόρθωση');
        app(DeleteNote::class)->handle($n, $admin);
        expect(Note::query()->count())->toBe(0);
    });
});

describe('admin panel for instructors', function (): void {
    it('lets instructors in with only their classes and notes', function (): void {
        $this->actingAs($this->instructor);

        $this->get('/admin')->assertOk()->assertDontSee(__('reporting.revenue_month'));
        $this->get('/admin/my-classes')->assertOk();
        $this->get('/admin/notes')->assertOk();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/payments')->assertForbidden();
        $this->get('/admin/expenses')->assertForbidden();
        $this->get('/admin/memberships')->assertForbidden();
    });

    it('requires 2FA for instructors too', function (): void {
        config(['identity.two_factor.enforced' => true]);

        $this->actingAs($this->instructor)->get('/admin/my-classes')
            ->assertRedirect(route('filament.admin.two-factor.setup'));
    });

    it('lists only the instructor\'s own classes and adds a note from the roster', function (): void {
        $foreign = bookableSession(['instructor_id' => $this->other->id]);
        $this->actingAs($this->instructor);

        Livewire::test(MyClasses::class)
            ->assertCanSeeTableRecords([$this->session])
            ->assertCanNotSeeTableRecords([$foreign])
            ->mountTableAction('participants', $this->session)
            ->assertSee($this->customer->fullName())
            ->unmountTableAction()
            ->callTableAction('note', $this->session, ['booking_id' => $this->booking->id, 'body' => 'Πολύ καλά', 'visible_to_customer' => true])
            ->assertHasNoTableActionErrors();

        expect(Note::query()->sole())
            ->user_id->toBe($this->customer->id)
            ->booking_id->toBe($this->booking->id);
    });

    it('shows instructors only notes about their customers', function (): void {
        $stranger = User::factory()->customer()->create();
        $mine = note($this->instructor, $this->customer);
        $hidden = note(User::factory()->admin()->create(), $stranger, ['body' => 'Άλλος πελάτης']);
        $this->actingAs($this->instructor);

        Livewire::test(ManageNotes::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$hidden])
            ->callAction('create', ['user_id' => $this->customer->id, 'body' => 'Νέα', 'booking_id' => $this->booking->id])
            ->assertHasNoActionErrors();

        expect(Note::query()->count())->toBe(3);
    });
});

describe('customer', function (): void {
    it('sees only the notes visible to them', function (): void {
        note($this->instructor, $this->customer, ['body' => 'Ορατή σημείωση', 'visible_to_customer' => true, 'booking_id' => $this->booking->id]);
        note($this->instructor, $this->customer, ['body' => 'Εσωτερική σημείωση']);

        $this->actingAs($this->customer)->get(route('app.membership'))
            ->assertOk()
            ->assertSee('Ορατή σημείωση')
            ->assertSee($this->instructor->fullName())
            ->assertDontSee('Εσωτερική σημείωση');
    });
});
