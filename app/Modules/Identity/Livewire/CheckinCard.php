<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Actions\RegenerateCheckinToken;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\CheckinCode;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * CI-01: the customer's digital card with a signed QR code, reissuable if it leaks.
 */
#[Layout('components.layouts.app')]
final class CheckinCard extends Component
{
    public bool $confirmingReissue = false;

    public ?string $status = null;

    public function reissue(RegenerateCheckinToken $regenerate): void
    {
        $regenerate->handle($this->user());

        $this->confirmingReissue = false;
        $this->status = __('identity.card.reissued');
    }

    public function render(): View
    {
        $user = $this->user()->refresh();

        return view('livewire.identity.checkin-card', [
            'user' => $user,
            'qr' => CheckinCode::svg($user),
        ])->title(__('identity.card.title'));
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
