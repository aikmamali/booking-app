<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ __('app.name') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-white text-gray-900 antialiased">
    <a href="#main" class="sr-only focus:not-sr-only">{{ __('app.skip_to_content') }}</a>
    <header class="border-b border-gray-200">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-4 pt-3">
            <a href="{{ auth()->check() ? route('app.home') : route('app.login') }}" class="font-semibold">{{ __('app.name') }}</a>
            <div class="flex items-center gap-3 whitespace-nowrap text-sm">
                @auth
                    <form method="POST" action="{{ route('app.logout') }}">
                        @csrf
                        <button type="submit" class="underline-offset-4 hover:underline">{{ __('identity.logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('app.login') }}" class="underline-offset-4 hover:underline">{{ __('identity.login.title') }}</a>
                    <a href="{{ route('app.register') }}" class="underline-offset-4 hover:underline">{{ __('identity.register.title') }}</a>
                @endauth
            </div>
        </div>
        <nav class="mx-auto flex max-w-3xl gap-1 overflow-x-auto px-2 pb-1 pt-2 text-sm" aria-label="{{ __('app.nav.label') }}">
            @auth
                @foreach ([['app.book', 'booking.book.nav'], ['app.bookings', 'booking.mine.nav'], ['app.membership', 'membership.mine.nav'], ['app.card', 'identity.card.nav'], ['app.profile', 'identity.profile.nav']] as [$route, $label])
                    <a href="{{ route($route) }}" @if (request()->routeIs($route, $route.'.*')) aria-current="page" @endif
                        @class(['whitespace-nowrap rounded-md px-2 py-1', 'bg-teal-50 font-semibold text-teal-800' => request()->routeIs($route, $route.'.*'), 'text-gray-700' => ! request()->routeIs($route, $route.'.*')])>{{ __($label) }}</a>
                @endforeach
            @endauth
        </nav>
    </header>
    <main id="main" class="mx-auto max-w-3xl px-4 py-6">
        {{ $slot }}
    </main>
    <footer class="mx-auto max-w-3xl px-4 pb-6 text-xs text-gray-600">
        <a href="{{ route('app.terms') }}" class="underline">{{ __('identity.legal.terms') }}</a>
        ·
        <a href="{{ route('app.privacy') }}" class="underline">{{ __('identity.legal.privacy') }}</a>
    </footer>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
        }
    </script>
</body>
</html>
