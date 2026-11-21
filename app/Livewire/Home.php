<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Landing page of the customer area (/app). Module screens (booking wizard, my bookings, ...)
 * live in their modules' Livewire folders and are linked from here.
 */
#[Layout('components.layouts.app')]
final class Home extends Component
{
    public function render(): View
    {
        return view('livewire.home')
            ->title(__('app.home.title'));
    }
}
