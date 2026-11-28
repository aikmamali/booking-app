<?php

declare(strict_types=1);

namespace App\Modules\Communication\Actions;

use App\Modules\Communication\Exceptions\NoteRejected;
use App\Modules\Communication\Models\Note;
use App\Modules\Communication\Support\NoteAccess;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class DeleteNote
{
    public function __construct(private NoteAccess $access) {}

    public function handle(Note $note, User $actor): void
    {
        if (! $this->access->canChange($actor, $note)) {
            throw NoteRejected::because('communication.notes.errors.not_yours');
        }

        DB::transaction(fn () => $note->delete());
    }
}
