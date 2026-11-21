<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Actions\UpdateProfile;
use App\Modules\Identity\Data\UpdateProfileData;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class EditProfileForm extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $phone_alt = '';

    public string $vat_number = '';

    public string $locale = 'el';

    public ?string $status = null;

    public function mount(): void
    {
        $user = $this->user();

        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->phone_alt = (string) $user->phone_alt;
        $this->vat_number = (string) $user->vat_number;
        $this->locale = $user->locale->value;
    }

    public function save(UpdateProfile $update): void
    {
        $user = $this->user();
        $previousEmail = $user->email;

        $data = UpdateProfileData::validateAndCreate([
            ...$this->only(['first_name', 'last_name', 'phone', 'locale']),
            'user_id' => $user->id,
            'email' => mb_strtolower(trim($this->email)),
            'phone_alt' => $this->phone_alt === '' ? null : $this->phone_alt,
            'vat_number' => $this->vat_number === '' ? null : $this->vat_number,
        ]);

        $update->handle($user, $data);

        if ($previousEmail !== $user->email) {
            $this->redirectRoute('verification.notice');

            return;
        }

        // Reload so a language change applies to the whole page.
        session()->flash('status', __('identity.profile.saved', [], $user->locale->value));
        $this->redirectRoute('app.profile');
    }

    public function render(): View
    {
        return view('livewire.identity.edit-profile-form');
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
