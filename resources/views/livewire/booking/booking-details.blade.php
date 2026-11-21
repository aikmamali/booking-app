<section class="space-y-6">
    <div>
        <a href="{{ route('app.bookings') }}" class="text-sm underline">← {{ __('booking.mine.title') }}</a>
        <h1 class="mt-2 text-2xl font-semibold">{{ $booking->session->service->name }}</h1>
        <p class="text-gray-700">{{ ucfirst($when->locale(app()->getLocale())->translatedFormat('l d/m/Y H:i')) }}</p>
    </div>
    <x-status />
    @if ($error)
        <p role="alert" class="rounded-md bg-red-50 p-3 text-sm text-red-800">{{ $error }}</p>
    @endif

    <dl class="grid grid-cols-2 gap-2 rounded-md border border-gray-200 p-4 text-sm">
        <dt class="text-gray-600">{{ __('booking.fields.reference') }}</dt><dd class="font-mono">{{ $booking->reference() }}</dd>
        <dt class="text-gray-600">{{ __('booking.fields.status') }}</dt><dd>{{ $booking->status->label() }}</dd>
        <dt class="text-gray-600">{{ __('scheduling.fields.instructor') }}</dt><dd>{{ $booking->session->instructor->fullName() }}</dd>
        <dt class="text-gray-600">{{ __('booking.fields.cost') }}</dt><dd>{{ __('booking.book.cost', ['credits' => \App\View\Format::credits($booking->credits_charged)]) }}</dd>
        @if ($booking->cancel_reason)
            <dt class="text-gray-600">{{ __('booking.fields.cancel_reason') }}</dt><dd>{{ $booking->cancel_reason === 'rescheduled' ? __('booking.history.rescheduled') : $booking->cancel_reason }}</dd>
        @endif
    </dl>

    @if ($booking->status->isActive())
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="{{ route('app.bookings.ics', $booking->public_id) }}" class="rounded-md border px-3 py-2">{{ __('booking.details.ics') }}</a>
            <a href="{{ $googleUrl }}" target="_blank" rel="noopener" class="rounded-md border px-3 py-2">{{ __('booking.details.google') }}</a>
        </div>

        <div class="space-y-3 rounded-md border border-gray-200 p-4">
            <h2 class="font-semibold">{{ __('booking.details.change') }}</h2>
            @if (! $terms->customerMayCancel())
                <p class="text-sm text-gray-700">{{ __('booking.details.too_late', ['time' => $deadline->format('d/m H:i')]) }}</p>
            @else
                <p class="text-sm text-gray-700">
                    {{ $terms->refundsCredit() ? __('booking.details.refund_until', ['time' => $deadline->format('d/m H:i')]) : __('booking.details.no_refund') }}
                </p>
                @if (! $confirmingCancel && ! $rescheduling)
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="$set('confirmingCancel', true)" class="rounded-md border border-red-300 px-3 py-2 text-sm text-red-800">{{ __('booking.details.cancel') }}</button>
                        @if ($terms->customerMayReschedule())
                            <button type="button" wire:click="$set('rescheduling', true)" class="rounded-md border px-3 py-2 text-sm">{{ __('booking.details.reschedule') }}</button>
                        @endif
                    </div>
                @endif
                @if ($confirmingCancel)
                    <form wire:submit="cancel" class="space-y-3">
                        <x-form.input name="reason" :label="__('booking.details.reason')" />
                        <div class="flex gap-2">
                            <x-form.button variant="danger">{{ __('booking.details.confirm_cancel') }}</x-form.button>
                            <button type="button" wire:click="$set('confirmingCancel', false)" class="rounded-md border px-3 py-2 text-sm">{{ __('booking.book.back') }}</button>
                        </div>
                    </form>
                @endif
                @if ($rescheduling)
                    <form wire:submit="reschedule" class="space-y-3">
                        <label for="newSessionId" class="block text-sm font-medium">{{ __('booking.details.new_time') }}</label>
                        <select id="newSessionId" wire:model="newSessionId" class="block w-full rounded-md border border-gray-300 px-3 py-2">
                            <option value="">—</option>
                            @foreach ($alternatives as $slot)
                                <option value="{{ $slot['public_id'] }}">{{ \Carbon\CarbonImmutable::parse($slot['date'])->locale(app()->getLocale())->translatedFormat('D d/m') }} {{ $slot['time'] }} ({{ trans_choice('booking.book.free_seats', $slot['free'], ['count' => $slot['free']]) }})</option>
                            @endforeach
                        </select>
                        <div class="flex gap-2">
                            <x-form.button>{{ __('booking.details.confirm_reschedule') }}</x-form.button>
                            <button type="button" wire:click="$set('rescheduling', false)" class="rounded-md border px-3 py-2 text-sm">{{ __('booking.book.back') }}</button>
                        </div>
                    </form>
                @endif
            @endif
        </div>
    @endif

    <div>
        <h2 class="mb-2 font-semibold">{{ __('booking.details.history') }}</h2>
        <ol class="space-y-1 text-sm">
            @foreach ($history as $entry)
                <li>
                    <span class="text-gray-600">{{ app(\App\Shared\BusinessTime::class)->toLocal(\Carbon\CarbonImmutable::parse($entry->created_at))->format('d/m H:i') }}</span>
                    · {{ __('booking.history.'.($entry->event ?? 'updated'), ['status' => __('booking.status.'.($entry->properties['attributes']['status'] ?? $booking->status->value))]) }}
                </li>
            @endforeach
        </ol>
    </div>
</section>
