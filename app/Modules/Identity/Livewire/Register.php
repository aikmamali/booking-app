<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Actions\RegisterCustomer;
use App\Modules\Identity\Data\RegisterCustomerData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class Register extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $accept_terms = false;

    public bool $marketing_consent = false;

    public function register(RegisterCustomer $register): void
    {
        $this->throttle();

        $data = RegisterCustomerData::validateAndCreate([
            ...$this->only([
                'first_name', 'last_name', 'phone', 'password', 'password_confirmation',
                'accept_terms', 'marketing_consent',
            ]),
            'email' => mb_strtolower(trim($this->email)),
            'locale' => app()->getLocale(),
        ]);

        $user = $register->handle($data);

        Auth::login($user);
        session()->regenerate();

        $this->redirectRoute('verification.notice');
    }

    public function render(): View
    {
        return view('livewire.identity.register')->title(__('identity.register.title'));
    }

    private function throttle(): void
    {
        $key = 'register|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        RateLimiter::hit($key, 60);
    }
}
