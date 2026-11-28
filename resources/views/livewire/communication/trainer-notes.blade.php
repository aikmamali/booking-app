<section aria-labelledby="trainer-notes-heading" @class(['hidden' => $notes->isEmpty()])>
    @if ($notes->isNotEmpty())
        <h2 id="trainer-notes-heading" class="mb-3 text-lg font-semibold">{{ __('communication.notes.mine_title') }}</h2>
        <ul class="space-y-3">
            @foreach ($notes as $note)
                <li class="rounded-md border border-gray-200 p-3">
                    <p class="text-sm text-gray-600">
                        {{ \App\View\Format::date(app(\App\Shared\BusinessTime::class)->toLocal($note->created_at)) }}
                        @if ($note->author) · {{ $note->author->fullName() }} @endif
                        @if ($note->booking_id && isset($bookings[$note->booking_id])) · {{ $bookings[$note->booking_id] }} @endif
                    </p>
                    <p class="mt-1 whitespace-pre-line">{{ $note->body }}</p>
                </li>
            @endforeach
        </ul>
    @endif
</section>
