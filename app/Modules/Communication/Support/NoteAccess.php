<?php

declare(strict_types=1);

namespace App\Modules\Communication\Support;

use App\Modules\Booking\Contracts\InstructorRoster;
use App\Modules\Communication\Models\Note;
use App\Modules\Identity\Models\User;

/**
 * Who may see and write which notes (ADR 0011). Staff with `manage_all_notes` (admin)
 * reach every customer; an instructor reaches the customers of their own classes and
 * edits only their own notes.
 */
final readonly class NoteAccess
{
    public const string MANAGE_ALL = 'manage_all_notes';

    public function __construct(private InstructorRoster $roster) {}

    public function managesAll(User $actor): bool
    {
        return $actor->can(self::MANAGE_ALL);
    }

    public function reachesCustomer(User $actor, int $customerId): bool
    {
        return $this->managesAll($actor) || $this->roster->teaches($actor->id, $customerId);
    }

    public function canChange(User $actor, Note $note): bool
    {
        return $this->managesAll($actor) || $note->author_id === $actor->id;
    }

    /**
     * Customers this actor may pick; null means all.
     *
     * @return list<int>|null
     */
    public function customerIds(User $actor): ?array
    {
        return $this->managesAll($actor) ? null : $this->roster->customerIds($actor->id);
    }

    public function instructorScope(User $actor): ?int
    {
        return $this->managesAll($actor) ? null : $actor->id;
    }
}
