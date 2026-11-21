<section class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold">{{ __('app.home.heading') }}, {{ auth()->user()?->first_name }}</h1>
        <p class="mt-2 text-gray-600">{{ __('app.home.intro') }}</p>
    </div>
    <x-status />

    @if ($announcements !== [])
        <div aria-label="{{ __('communication.announcements.title') }}" role="region" class="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-2">
            @foreach ($announcements as $announcement)
                <article class="w-72 shrink-0 snap-start overflow-hidden rounded-md border border-gray-200 bg-white">
                    @if ($announcement['image_url'])
                        <img src="{{ $announcement['image_url'] }}" alt="" class="h-32 w-full object-cover">
                    @endif
                    <div class="p-3">
                        <h2 class="font-semibold">{{ $announcement['title'] }}</h2>
                        @if ($announcement['body'])<p class="mt-1 text-sm text-gray-700">{{ $announcement['body'] }}</p>@endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
    <a href="{{ route('app.book') }}" class="inline-block rounded-md bg-teal-700 px-4 py-2 font-semibold text-white">{{ __('booking.book.title') }}</a>

    <div>
        <h2 class="mb-2 text-lg font-semibold">{{ __('booking.mine.next') }}</h2>
        @forelse ($upcoming as $booking)
            <a href="{{ route('app.bookings.show', $booking->publicId) }}" class="mb-2 block rounded-md border border-gray-200 p-3 hover:border-teal-600">
                <span class="font-semibold">{{ ucfirst(app(\App\Shared\BusinessTime::class)->toLocal($booking->startsAt)->locale(app()->getLocale())->translatedFormat('D d/m H:i')) }}</span>
                · {{ $booking->serviceName }}
            </a>
        @empty
            <p class="text-gray-700">{{ __('booking.mine.none_upcoming') }}</p>
        @endforelse
    </div>
</section>
