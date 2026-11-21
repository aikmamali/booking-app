@php use App\Modules\Booking\Livewire\MyBookings as M; @endphp
<section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-2xl font-semibold">{{ __('booking.mine.title') }}</h1>
        <a href="{{ route('app.book') }}" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white">{{ __('booking.book.title') }}</a>
    </div>
    <x-status />

    <div role="tablist" class="flex gap-1 overflow-x-auto border-b border-gray-200 text-sm">
        @foreach (M::VIEWS as $tab)
            <button type="button" role="tab" aria-selected="{{ $view === $tab ? 'true' : 'false' }}" wire:click="show('{{ $tab }}')"
                @class(['whitespace-nowrap px-3 py-2', 'border-b-2 border-teal-700 font-semibold text-teal-800' => $view === $tab, 'text-gray-600' => $view !== $tab])>
                {{ __('booking.mine.views.'.$tab) }}
            </button>
        @endforeach
    </div>

    @if ($from !== null)
        <div class="flex items-center justify-between text-sm">
            <button type="button" wire:click="move(-1)" class="rounded-md border px-3 py-1" aria-label="{{ __('booking.mine.previous') }}">‹</button>
            <span class="font-medium">{{ $from->format('d/m') }} – {{ $to->format('d/m/Y') }}</span>
            <button type="button" wire:click="move(1)" class="rounded-md border px-3 py-1" aria-label="{{ __('booking.mine.next') }}">›</button>
        </div>
    @endif

    @if ($waitlist->isNotEmpty())
        <div class="rounded-md border border-amber-200 bg-amber-50 p-3">
            <h2 class="mb-2 text-sm font-semibold">{{ __('booking.waitlist.title') }}</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($waitlist as $entry)
                    <li class="flex flex-wrap items-center justify-between gap-2">
                        <span>
                            {{ app(\App\Shared\BusinessTime::class)->toLocal($entry->session->starts_at)->format('d/m H:i') }} · {{ $entry->session->service->name }}
                            —
                            @if ($entry->status === \App\Modules\Booking\Enums\WaitlistStatus::Offered)
                                <strong>{{ __('booking.waitlist.offer_until', ['time' => app(\App\Shared\BusinessTime::class)->toLocal($entry->offered_until)->format('H:i')]) }}</strong>
                            @else
                                {{ __('booking.waitlist.your_position', ['position' => $entry->position()]) }}
                            @endif
                        </span>
                        <span class="flex gap-2">
                            @if ($entry->status === \App\Modules\Booking\Enums\WaitlistStatus::Offered)
                                <button type="button" wire:click="acceptOffer('{{ $entry->public_id }}')" class="rounded-md bg-teal-700 px-3 py-1 font-semibold text-white">{{ __('booking.waitlist.accept') }}</button>
                            @endif
                            <button type="button" wire:click="leaveWaitlist('{{ $entry->public_id }}')" class="underline">{{ __('booking.waitlist.leave') }}</button>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @forelse ($groups as $day => $bookings)
        <div>
            <h2 class="mb-2 text-sm font-semibold text-gray-700">{{ ucfirst(\Carbon\CarbonImmutable::parse($day)->locale(app()->getLocale())->translatedFormat('l d/m/Y')) }}</h2>
            <ul class="space-y-2">
                @foreach ($bookings as $booking)
                    <li>
                        <a href="{{ route('app.bookings.show', $booking->public_id) }}" class="flex items-center justify-between rounded-md border border-gray-200 p-3 hover:border-teal-600">
                            <span>
                                <span class="font-semibold">{{ app(\App\Shared\BusinessTime::class)->toLocal($booking->session->starts_at)->format('H:i') }}</span>
                                <span class="ml-2">{{ $booking->session->service->name }}</span>
                            </span>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs">{{ $booking->status->label() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <p class="text-gray-700">{{ __('booking.mine.empty') }}</p>
    @endforelse
</section>
