<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Actions\ResetPassword;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ResetPasswordForm extends Component
{
    #[Locked]
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function save(ResetPassword $reset): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $reset->handle(mb_strtolower(trim($this->email)), $this->token, $this->password, $this->broker());

        session()->flash('status', __($this->successMessage()));

        $this->redirectRoute('app.login');
    }

    public function render(): View
    {
        return view('livewire.identity.reset-password', ['heading' => __($this->headingKey())])
            ->title(__($this->headingKey()));
    }

    protected function broker(): string
    {
        return ResetPassword::RESET_BROKER;
    }

    protected function headingKey(): string
    {
        return 'identity.reset.title';
    }

    protected function successMessage(): string
    {
        return 'passwords.reset';
    }
}
