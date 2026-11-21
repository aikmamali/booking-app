@php use App\Modules\Booking\Support\Availability as A; @endphp
<section class="space-y-6">
    <div class="flex items-center gap-3">
        @if ($serviceId !== null)
            <button type="button" wire:click="back" class="rounded-md border border-gray-300 px-3 py-1 text-sm">← {{ __('booking.book.back') }}</button>
        @endif
        <h1 class="text-2xl font-semibold">{{ __('booking.book.title') }}</h1>
    </div>

    @if ($services !== null)
        <h2 class="text-lg font-semibold">{{ __('booking.book.choose_service') }}</h2>
        <ul class="grid gap-3 sm:grid-cols-2">
            @foreach ($services as $item)
                <li>
                    <button type="button" wire:click="chooseService({{ $item->id }})"
                        class="w-full rounded-md border border-gray-200 p-4 text-left hover:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600">
                        <span class="block font-semibold">{{ $item->name }}</span>
                        <span class="block text-sm text-gray-600">{{ $item->duration_minutes }}′ · {{ __('booking.book.cost', ['credits' => \App\View\Format::credits($item->credit_cost)]) }}</span>
                        @if ($item->description)<span class="mt-1 block text-sm text-gray-700">{{ $item->description }}</span>@endif
                    </button>
                </li>
            @endforeach
        </ul>
    @elseif ($date === null)
        <div class="flex items-center justify-between">
            <button type="button" wire:click="moveMonth(-1)" class="rounded-md border px-3 py-1" aria-label="{{ __('booking.book.previous_month') }}">‹</button>
            <h2 class="text-lg font-semibold">{{ $service->name }} · {{ ucfirst($monthStart->locale(app()->getLocale())->translatedFormat('F Y')) }}</h2>
            <button type="button" wire:click="moveMonth(1)" class="rounded-md border px-3 py-1" aria-label="{{ __('booking.book.next_month') }}">›</button>
        </div>
        <div class="grid grid-cols-7 gap-1 text-center text-sm" role="grid" aria-label="{{ __('booking.book.calendar') }}">
            @foreach (range(1, 7) as $wd)
                <div class="py-1 font-medium text-gray-600" role="columnheader">{{ mb_substr(__('scheduling.weekdays.'.$wd), 0, 3) }}</div>
            @endforeach
            @for ($i = 1; $i < $monthStart->dayOfWeekIso; $i++)<div></div>@endfor
            @for ($d = $monthStart; $d->month === $monthStart->month; $d = $d->addDay())
                @php($key = $d->format('Y-m-d'))
                @php($state = $days[$key] ?? null)
                @if ($state === A::AVAILABLE || $state === A::FULL)
                    <button type="button" wire:click="chooseDate('{{ $key }}')" role="gridcell"
                        aria-label="{{ $d->format('d/m') }}: {{ __('booking.day_states.'.$state) }}"
                        @class(['rounded-md py-2 focus:outline-none focus:ring-2 focus:ring-teal-600',
                            'bg-teal-600 font-semibold text-white' => $state === A::AVAILABLE,
                            'bg-amber-100 text-amber-900' => $state === A::FULL])>
                        {{ $d->day }}
                    </button>
                @else
                    <div role="gridcell" aria-label="{{ $d->format('d/m') }}{{ $state ? ': '.__('booking.day_states.'.$state) : '' }}"
                        @class(['rounded-md py-2', 'text-gray-400' => $key < $today || $state === A::PAST, 'bg-gray-100 text-gray-500' => $state === A::CLOSED])>
                        {{ $d->day }}
                    </div>
                @endif
            @endfor
        </div>
        <ul class="flex flex-wrap gap-4 text-xs text-gray-700" aria-label="{{ __('booking.book.legend') }}">
            <li><span class="inline-block h-3 w-3 rounded bg-teal-600 align-middle"></span> {{ __('booking.day_states.available') }}</li>
            <li><span class="inline-block h-3 w-3 rounded bg-amber-100 align-middle"></span> {{ __('booking.day_states.full') }}</li>
            <li><span class="inline-block h-3 w-3 rounded bg-gray-100 align-middle"></span> {{ __('booking.day_states.closed') }}</li>
        </ul>
    @elseif ($summary === null)
        <h2 class="text-lg font-semibold">{{ $service->name }} · {{ \Carbon\CarbonImmutable::parse($date)->locale(app()->getLocale())->translatedFormat('l d/m') }}</h2>
        <x-status />
        @if ($error)
            <p role="alert" class="rounded-md bg-red-50 p-3 text-sm text-red-800">{{ $error }}</p>
        @endif
        <ul class="space-y-2">
            @forelse ($slots as $slot)
                <li class="flex items-center justify-between rounded-md border border-gray-200 p-3">
                    <div>
                        <span class="text-lg font-semibold">{{ $slot['time'] }}</span>
                        <span class="ml-2 text-sm text-gray-700">
                            @if ($slot['mine']) {{ __('booking.book.already_mine') }}
                            @elseif ($slot['state'] === A::AVAILABLE) {{ trans_choice('booking.book.free_seats', $slot['free'], ['count' => $slot['free']]) }}
                            @else {{ __('booking.day_states.'.$slot['state']) }} @endif
                        </span>
                    </div>
                    @if ($slot['state'] === A::AVAILABLE && ! $slot['mine'])
                        <button type="button" wire:click="chooseSession('{{ $slot['public_id'] }}')" class="rounded-md bg-teal-700 px-4 py-2 font-semibold text-white">{{ __('booking.book.select') }}</button>
                    @elseif ($slot['my_position'] !== null)
                        <div class="text-right text-sm">
                            <span class="block">{{ __('booking.waitlist.your_position', ['position' => $slot['my_position']]) }}</span>
                            <button type="button" wire:click="leaveWaitlist('{{ $slot['public_id'] }}')" class="underline">{{ __('booking.waitlist.leave') }}</button>
                        </div>
                    @elseif ($slot['state'] === A::FULL && ! $slot['mine'] && $slot['waitlist_open'])
                        <button type="button" wire:click="joinWaitlist('{{ $slot['public_id'] }}')" class="rounded-md border border-teal-700 px-3 py-2 text-sm text-teal-800">
                            {{ __('booking.waitlist.join') }}
                            <span class="block text-xs">{{ trans_choice('booking.waitlist.waiting', $slot['waiting'], ['count' => $slot['waiting']]) }}</span>
                        </button>
                    @endif
                </li>
            @empty
                <li class="text-gray-700">{{ __('booking.book.no_sessions') }}</li>
            @endforelse
        </ul>
    @else
        <div class="space-y-3 rounded-md border border-gray-200 p-4">
            <h2 class="text-lg font-semibold">{{ __('booking.book.confirm_title') }}</h2>
            <dl class="grid grid-cols-2 gap-2 text-sm">
                <dt class="text-gray-600">{{ __('catalog.service.label') }}</dt><dd>{{ $service->name }}</dd>
                <dt class="text-gray-600">{{ __('booking.fields.when') }}</dt><dd>{{ $summary['when']->locale(app()->getLocale())->translatedFormat('l d/m/Y H:i') }}</dd>
                <dt class="text-gray-600">{{ __('scheduling.fields.instructor') }}</dt><dd>{{ $summary['session']->instructor->fullName() }}</dd>
                <dt class="text-gray-600">{{ __('booking.fields.cost') }}</dt><dd>{{ __('booking.book.cost', ['credits' => $summary['cost']]) }}</dd>
            </dl>
            <p class="text-sm text-gray-700">{{ __('booking.book.cancel_until', ['time' => $summary['deadline']->format('d/m H:i')]) }}</p>
            @if (! $summary['payable'])
                <p role="alert" class="rounded-md bg-amber-50 p-3 text-sm text-amber-900">{{ __((string) $summary['rejection']) }} <a href="{{ route('app.membership') }}" class="underline">{{ __('membership.mine.nav') }}</a></p>
            @endif
            @if ($error)
                <p role="alert" class="rounded-md bg-red-50 p-3 text-sm text-red-800">{{ $error }}</p>
            @endif
            <x-form.checkbox name="remind">{{ __('booking.book.remind') }}</x-form.checkbox>
            <button type="button" wire:click="confirm" wire:loading.attr="disabled" @disabled(! $summary['payable'])
                class="w-full rounded-md bg-teal-700 px-4 py-2 font-semibold text-white disabled:opacity-50 sm:w-auto">{{ __('booking.book.confirm') }}</button>
        </div>
    @endif
</section>
