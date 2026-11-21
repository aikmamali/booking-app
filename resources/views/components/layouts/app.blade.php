<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ __('app.name') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-white text-gray-900 antialiased">
    <a href="#main" class="sr-only focus:not-sr-only">{{ __('app.skip_to_content') }}</a>
    <header class="border-b border-gray-200">
        <nav class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-4 py-3" aria-label="{{ __('app.nav.label') }}">
            <a href="{{ auth()->check() ? route('app.home') : route('app.login') }}" class="font-semibold">{{ __('app.name') }}</a>
            <div class="flex items-center gap-3 whitespace-nowrap text-sm sm:gap-4">
                @auth
                    <a href="{{ route('app.membership') }}" class="underline-offset-4 hover:underline">{{ __('membership.mine.nav') }}</a>
                    <a href="{{ route('app.profile') }}" class="underline-offset-4 hover:underline">{{ __('identity.profile.nav') }}</a>
                    <form method="POST" action="{{ route('app.logout') }}">
                        @csrf
                        <button type="submit" class="underline-offset-4 hover:underline">{{ __('identity.logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('app.login') }}" class="underline-offset-4 hover:underline">{{ __('identity.login.title') }}</a>
                    <a href="{{ route('app.register') }}" class="underline-offset-4 hover:underline">{{ __('identity.register.title') }}</a>
                @endauth
            </div>
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
</body>
</html>
