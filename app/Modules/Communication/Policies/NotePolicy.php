<?php

declare(strict_types=1);

namespace App\Modules\Communication\Policies;

use App\Modules\Communication\Models\Note;
use App\Modules\Communication\Support\NoteAccess;
use App\Modules\Identity\Models\User;
use App\Shared\Policies\PermissionPolicy;
use Illuminate\Contracts\Auth\Access\Authorizable;

final class NotePolicy extends PermissionPolicy
{
    public function __construct(private readonly NoteAccess $access) {}

    protected function subject(): string
    {
        return 'note';
    }

    public function view(Authorizable $actor, ?Note $note = null): bool
    {
        return parent::view($actor)
            && (! $note instanceof Note || ($actor instanceof User && $this->access->reachesCustomer($actor, $note->user_id)));
    }

    public function update(Authorizable $actor, ?Note $note = null): bool
    {
        return parent::update($actor) && $this->owns($actor, $note);
    }

    public function delete(Authorizable $actor, ?Note $note = null): bool
    {
        return parent::delete($actor) && $this->owns($actor, $note);
    }

    private function owns(Authorizable $actor, ?Note $note): bool
    {
        return ! $note instanceof Note || ($actor instanceof User && $this->access->canChange($actor, $note));
    }
}
