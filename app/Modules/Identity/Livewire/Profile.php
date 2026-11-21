<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class Profile extends Component
{
    public function render(): View
    {
        return view('livewire.identity.profile')->title(__('identity.profile.title'));
    }
}
