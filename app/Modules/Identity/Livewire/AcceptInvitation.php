<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Actions\ResetPassword;

/**
 * ID-05: the invited person chooses their first password. Same form as a reset, with the
 * invitation token broker.
 */
final class AcceptInvitation extends ResetPasswordForm
{
    protected function broker(): string
    {
        return ResetPassword::INVITATION_BROKER;
    }

    protected function headingKey(): string
    {
        return 'identity.invitation.title';
    }

    protected function successMessage(): string
    {
        return 'identity.invitation.accepted';
    }
}
