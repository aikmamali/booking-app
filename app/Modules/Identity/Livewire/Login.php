<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Actions\AuthenticateUser;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(AuthenticateUser $authenticate): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $authenticate->handle($this->email, $this->password, $this->remember, (string) request()->ip());

        session()->regenerate();

        $this->redirectIntended(route('app.home'), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.identity.login')->title(__('identity.login.title'));
    }
}
