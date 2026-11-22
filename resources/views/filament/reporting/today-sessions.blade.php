<x-filament-widgets::widget>
    <x-filament::section :heading="__('reporting.today_sessions')">
        @if ($rows === [])
            <p class="text-sm text-gray-500">{{ __('reporting.no_sessions_today') }}</p>
        @else
            <table class="w-full text-left text-sm">
                <thead class="text-gray-500">
                    <tr>
                        <th scope="col" class="py-1">{{ __('reporting.time') }}</th>
                        <th scope="col" class="py-1">{{ __('catalog.service.label') }}</th>
                        <th scope="col" class="py-1">{{ __('scheduling.fields.instructor') }}</th>
                        <th scope="col" class="py-1 text-right">{{ __('reporting.occupancy') }}</th>
                        <th scope="col" class="py-1 text-right">{{ __('booking.waitlist.title') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="border-t border-gray-100 dark:border-white/10">
                            <td class="py-2">{{ $row->startsAt->setTimezone($timezone)->format('H:i') }}</td>
                            <td class="py-2">{{ $row->serviceName }} @if ($row->cancelled)<span class="text-danger-600">({{ __('scheduling.status.cancelled') }})</span>@endif</td>
                            <td class="py-2">{{ $row->instructorName }}</td>
                            <td class="py-2 text-right">{{ $row->booked }}/{{ $row->capacity }} · {{ $row->percent() }}%</td>
                            <td class="py-2 text-right">{{ $row->waiting }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
