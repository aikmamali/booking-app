<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Actions\ChangePassword;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

final class ChangePasswordForm extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $status = null;

    public function save(ChangePassword $change): void
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        /** @var User $user */
        $user = auth()->user();

        $change->handle($user, $this->current_password, $this->password);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->status = __('identity.password.changed');
    }

    public function render(): View
    {
        return view('livewire.identity.change-password-form');
    }
}
