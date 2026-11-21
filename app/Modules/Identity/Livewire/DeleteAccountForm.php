<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Actions\DeleteAccount;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class DeleteAccountForm extends Component
{
    public string $password = '';

    public bool $confirm = false;

    public function delete(DeleteAccount $deleteAccount): void
    {
        $this->validate([
            'password' => ['required', 'string'],
            'confirm' => ['accepted'],
        ]);

        /** @var User $user */
        $user = auth()->user();

        $deleteAccount->handle($user, $this->password);

        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        session()->flash('status', __('identity.delete.done'));
        $this->redirectRoute('app.login');
    }

    public function render(): View
    {
        return view('livewire.identity.delete-account-form');
    }
}
