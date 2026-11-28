<?php

declare(strict_types=1);

namespace App\Modules\Identity\Filament\Auth;

use App\Modules\Identity\Actions\ConfirmTwoFactorSetup;
use App\Modules\Identity\Actions\RegenerateRecoveryCodes;
use App\Modules\Identity\Actions\StartTwoFactorSetup;
use App\Modules\Identity\Exceptions\TwoFactorRejected;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\Totp;
use App\Modules\Identity\Support\TwoFactorSession;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\SimplePage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

/**
 * ID-07: first-time setup (scan the QR, confirm one code, keep the recovery codes) and,
 * once enabled, a place to issue new recovery codes.
 *
 * @property Form $form
 */
final class TwoFactorSetup extends SimplePage implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /**
     * Shown once, right after they are issued.
     *
     * @var list<string>
     */
    #[Locked]
    public array $recoveryCodes = [];

    protected static string $view = 'filament.identity.two-factor-setup';

    public function mount(StartTwoFactorSetup $start): void
    {
        $user = $this->user();

        // Reuse a pending secret so a reload does not invalidate a QR that was just scanned.
        if (! $user->hasTwoFactorEnabled() && $user->two_factor_secret === null) {
            $start->handle($user);
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('code')->label(__('identity.two_factor.code'))
                ->required()->autocomplete('one-time-code')->maxLength(10),
        ])->statePath('data');
    }

    public function confirm(ConfirmTwoFactorSetup $confirm, TwoFactorSession $session): void
    {
        $user = $this->user();

        try {
            $this->recoveryCodes = $confirm->handle($user, (string) ($this->form->getState()['code'] ?? ''));
        } catch (TwoFactorRejected $e) {
            throw ValidationException::withMessages(['data.code' => $e->translated()]);
        }

        // Proving the code just now counts as passing the challenge for this session.
        $session->markPassed($user);
    }

    public function regenerate(RegenerateRecoveryCodes $regenerate): void
    {
        $this->recoveryCodes = $regenerate->handle($this->user());
    }

    public function isEnabled(): bool
    {
        return $this->user()->hasTwoFactorEnabled();
    }

    public function qrSvg(): string
    {
        $user = $this->user();

        return (new Totp)->qrSvg($user, (string) $user->two_factor_secret);
    }

    public function secret(): string
    {
        return (string) $this->user()->two_factor_secret;
    }

    public function getTitle(): string
    {
        return __('identity.two_factor.setup_title');
    }

    private function user(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user->refresh();
    }
}
