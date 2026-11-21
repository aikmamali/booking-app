<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d9488">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ __('app.name') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-white text-gray-900 antialiased">
    <a href="#main" class="sr-only focus:not-sr-only">{{ __('app.skip_to_content') }}</a>
    <header class="border-b border-gray-200">
        <nav class="mx-auto flex max-w-3xl items-center justify-between px-4 py-3" aria-label="{{ __('app.nav.label') }}">
            <a href="{{ route('app.home') }}" class="font-semibold">{{ __('app.name') }}</a>
        </nav>
    </header>
    <main id="main" class="mx-auto max-w-3xl px-4 py-6">
        {{ $slot }}
    </main>
</body>
</html>
