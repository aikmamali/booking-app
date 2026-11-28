<?php

declare(strict_types=1);

namespace App\Modules\Identity\Filament\Auth;

use App\Modules\Identity\Actions\VerifyTwoFactorChallenge;
use App\Modules\Identity\Exceptions\TwoFactorRejected;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\TwoFactorSession;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\SimplePage;
use Illuminate\Validation\ValidationException;

/**
 * ID-07: asked after every admin login, before any other admin page.
 *
 * @property Form $form
 */
final class TwoFactorChallenge extends SimplePage implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    protected static string $view = 'filament.identity.two-factor-challenge';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('code')->label(__('identity.two_factor.code'))
                ->helperText(__('identity.two_factor.code_hint'))
                ->required()->autofocus()->autocomplete('one-time-code')->maxLength(20),
        ])->statePath('data');
    }

    public function verify(VerifyTwoFactorChallenge $verify, TwoFactorSession $session): void
    {
        $user = $this->user();

        try {
            $verify->handle($user, (string) ($this->form->getState()['code'] ?? ''));
        } catch (TwoFactorRejected $e) {
            throw ValidationException::withMessages(['data.code' => $e->translated()]);
        }

        $session->markPassed($user);

        // The page the middleware interrupted (redirect()->guest() stored it), else the dashboard.
        $this->redirect((string) session()->pull('url.intended', filament()->getUrl()));
    }

    public function getTitle(): string
    {
        return __('identity.two_factor.challenge_title');
    }

    public function getSubheading(): string
    {
        return __('identity.two_factor.challenge_intro');
    }

    private function user(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
