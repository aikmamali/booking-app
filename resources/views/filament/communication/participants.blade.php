@if ($attendees === [])
    <p class="text-sm text-gray-500">{{ __('communication.my_classes.no_participants') }}</p>
@else
    <ul class="divide-y divide-gray-100 dark:divide-white/10" data-participants>
        @foreach ($attendees as $attendee)
            <li class="flex items-center justify-between py-2 text-sm">
                <span>{{ $attendee->name }}</span>
                <span class="text-gray-500">{{ $attendee->status }}</span>
            </li>
        @endforeach
    </ul>
@endif
