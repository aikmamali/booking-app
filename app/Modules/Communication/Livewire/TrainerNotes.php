<?php

declare(strict_types=1);

namespace App\Modules\Communication\Livewire;

use App\Modules\Booking\Contracts\InstructorRoster;
use App\Modules\Communication\Models\Note;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

/**
 * RP-03, customer side: the trainer notes marked visible to the customer, newest first.
 * Renders nothing when there are none.
 */
final class TrainerNotes extends Component
{
    public function render(InstructorRoster $roster): View
    {
        $notes = $this->notes();
        $bookingIds = array_values($notes->pluck('booking_id')->filter()->map(fn (mixed $id): int => (int) $id)->all());

        return view('livewire.communication.trainer-notes', [
            'notes' => $notes,
            'bookings' => $roster->labels($bookingIds),
        ]);
    }

    /**
     * @return Collection<int, Note>
     */
    private function notes(): Collection
    {
        return Note::query()->with('author')
            ->where('user_id', auth()->id())
            ->where('visible_to_customer', true)
            ->latest()
            ->limit(50)
            ->get();
    }
}
