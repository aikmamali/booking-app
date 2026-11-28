<?php

declare(strict_types=1);

namespace App\Modules\Communication\Actions;

use App\Modules\Booking\Contracts\InstructorRoster;
use App\Modules\Communication\Data\NoteData;
use App\Modules\Communication\Exceptions\NoteRejected;
use App\Modules\Communication\Models\Note;
use App\Modules\Communication\Support\NoteAccess;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * RP-03: writes or edits a trainer note. An instructor writes only about customers of
 * their own classes and edits only their own notes; a linked booking must be one of
 * that customer's bookings (in the instructor's classes).
 */
final readonly class SaveNote
{
    private const int MAX_LENGTH = 5000;

    public function __construct(
        private NoteAccess $access,
        private InstructorRoster $roster,
    ) {}

    public function handle(NoteData $data, User $author, ?Note $note = null): Note
    {
        $body = trim($data->body);

        if ($body === '' || mb_strlen($body) > self::MAX_LENGTH) {
            throw NoteRejected::because('communication.notes.errors.body');
        }

        $this->authorize($data, $author, $note);

        return DB::transaction(function () use ($data, $author, $note, $body): Note {
            $note ??= new Note(['author_id' => $author->id]);
            $note->fill([
                'user_id' => $data->user_id,
                'booking_id' => $data->booking_id,
                'body' => $body,
                'visible_to_customer' => $data->visible_to_customer,
            ])->save();

            return $note;
        });
    }

    private function authorize(NoteData $data, User $author, ?Note $note): void
    {
        if ($note instanceof Note && ! $this->access->canChange($author, $note)) {
            throw NoteRejected::because('communication.notes.errors.not_yours');
        }

        if (! $this->access->reachesCustomer($author, $data->user_id)) {
            throw NoteRejected::because('communication.notes.errors.not_your_customer');
        }

        if ($data->booking_id !== null
            && ! $this->roster->bookingBelongs($data->booking_id, $data->user_id, $this->access->instructorScope($author))) {
            throw NoteRejected::because('communication.notes.errors.booking');
        }
    }
}
