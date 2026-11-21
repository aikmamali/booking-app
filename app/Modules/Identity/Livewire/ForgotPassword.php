<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Actions\SendPasswordResetLink;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class ForgotPassword extends Component
{
    public string $email = '';

    public ?string $status = null;

    public function send(SendPasswordResetLink $send): void
    {
        $this->validate(['email' => ['required', 'string', 'email']]);

        $send->handle(mb_strtolower(trim($this->email)));

        $this->status = __('passwords.sent');
    }

    public function render(): View
    {
        return view('livewire.identity.forgot-password')->title(__('identity.forgot.title'));
    }
}
