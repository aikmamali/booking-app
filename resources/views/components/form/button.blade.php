@props(['variant' => 'primary'])

@php
    $classes = $variant === 'danger'
        ? 'bg-red-700 hover:bg-red-800 focus:ring-red-700'
        : 'bg-teal-700 hover:bg-teal-800 focus:ring-teal-700';
@endphp

<button {{ $attributes->merge(['type' => 'submit', 'class' => "inline-flex w-full justify-center rounded-md px-4 py-2 font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-60 sm:w-auto $classes"]) }}
    wire:loading.attr="disabled">
    {{ $slot }}
</button>
