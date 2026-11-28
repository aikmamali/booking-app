@php use App\Modules\Booking\Enums\FixedSeatStatus; @endphp
<section class="space-y-6">
    <h1 class="text-2xl font-semibold">{{ __('booking.fixed_seats.mine_title') }}</h1>
    <p class="text-sm text-gray-700">{{ __('booking.fixed_seats.intro') }}</p>
    <x-status />

    @if ($seats->isNotEmpty())
        <ul class="space-y-3">
            @foreach ($seats as $seat)
                @php($class = $classes[$seat->schedule_id] ?? null)
                <li class="rounded-md border border-gray-200 p-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold">{{ $class?->label() ?? '—' }}</p>
                            <p class="text-sm text-gray-600">{{ $seat->season->label() }}</p>
                        </div>
                        <span @class([
                            'rounded-full px-2 py-0.5 text-sm',
                            'bg-teal-100 text-teal-900' => $seat->status === FixedSeatStatus::Active,
                            'bg-amber-100 text-amber-900' => $seat->status === FixedSeatStatus::Requested,
                            'bg-gray-100 text-gray-800' => ! $seat->status->holdsPlace(),
                        ])>{{ $seat->status->label() }}</span>
                    </div>
                    @if ($seat->revoked_reason)
                        <p class="mt-1 text-sm text-gray-700">{{ $seat->revoked_reason }}</p>
                    @endif
                    @if ($seat->status->holdsPlace())
                        <button type="button" wire:click="leave('{{ $seat->public_id }}')" wire:confirm="{{ __('booking.fixed_seats.leave_confirm') }}"
                            class="mt-2 text-sm underline">{{ $seat->status === FixedSeatStatus::Requested ? __('booking.fixed_seats.withdraw') : __('booking.fixed_seats.leave') }}</button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <section aria-labelledby="request-heading" class="rounded-md border border-gray-200 p-4">
        <h2 id="request-heading" class="mb-3 text-lg font-semibold">{{ __('booking.fixed_seats.request_title') }}</h2>
        @if ($seasons->isEmpty())
            <p class="text-sm text-gray-700">{{ __('booking.fixed_seats.no_season') }}</p>
        @else
            <form wire:submit="request" class="space-y-3">
                <div>
                    <label for="season" class="block text-sm font-medium">{{ __('booking.fixed_seats.season') }}</label>
                    <select id="season" wire:model.live="seasonId" class="mt-1 w-full rounded-md border-gray-300">
                        @foreach ($seasons as $season)
                            <option value="{{ $season->id }}">{{ $season->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="schedule" class="block text-sm font-medium">{{ __('booking.fixed_seats.class') }}</label>
                    <select id="schedule" wire:model="scheduleId" class="mt-1 w-full rounded-md border-gray-300" @error('scheduleId') aria-invalid="true" aria-describedby="schedule-error" @enderror>
                        <option value="">{{ __('booking.fixed_seats.choose') }}</option>
                        @foreach ($choices as $choice)
                            <option value="{{ $choice->id }}">{{ $choice->label() }}</option>
                        @endforeach
                    </select>
                    @error('scheduleId') <p id="schedule-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white">{{ __('booking.fixed_seats.request') }}</button>
            </form>
        @endif
    </section>
</section>
