<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class VerifyEmailNotice extends Component
{
    public ?string $status = null;

    public function mount(): void
    {
        if ($this->user()->hasVerifiedEmail()) {
            $this->redirectRoute('app.home');
        }
    }

    public function resend(): void
    {
        $key = 'verify-resend|'.$this->user()->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->status = __('passwords.throttled');

            return;
        }

        RateLimiter::hit($key, 60);
        $this->user()->sendEmailVerificationNotification();
        $this->status = __('identity.verify.resent');
    }

    public function render(): View
    {
        return view('livewire.identity.verify-email')->title(__('identity.verify.title'));
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
