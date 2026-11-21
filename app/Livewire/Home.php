<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Modules\Booking\Contracts\BookingSummaries;
use App\Shared\Clock;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Landing page of the customer area (/app): next classes and the way to book.
 */
#[Layout('components.layouts.app')]
final class Home extends Component
{
    public function render(BookingSummaries $bookings, Clock $clock): View
    {
        return view('livewire.home', [
            'upcoming' => $bookings->upcomingFor((int) auth()->id(), $clock->now()),
        ])->title(__('app.home.title'));
    }
}
