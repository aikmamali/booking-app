<?php

declare(strict_types=1);

namespace App\Modules\Communication\Livewire;

use App\Modules\Communication\Actions\SaveNotificationPreferences;
use App\Modules\Communication\Support\Preferences;
use App\Modules\Identity\Actions\UpdateMarketingConsent;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * CM-02 on the profile page: email/SMS per notification type, and marketing consent.
 */
final class NotificationPreferencesForm extends Component
{
    /** @var array<string, array<string, bool>> */
    public array $matrix = [];

    public bool $marketing = false;

    public ?string $status = null;

    public function mount(Preferences $preferences): void
    {
        $this->matrix = $preferences->matrix($this->user()->id);
        $this->marketing = $this->user()->marketing_consent_at !== null;
    }

    public function save(SaveNotificationPreferences $save, UpdateMarketingConsent $consent): void
    {
        $save->handle($this->user()->id, $this->matrix);
        $consent->handle($this->user(), $this->marketing);

        $this->status = __('communication.preferences.saved');
    }

    public function render(): View
    {
        return view('livewire.communication.notification-preferences-form');
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
