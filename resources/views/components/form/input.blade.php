@props(['name', 'label', 'type' => 'text', 'autocomplete' => null, 'hint' => null, 'required' => false])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium">
        {{ $label }}@if ($required)<span aria-hidden="true"> *</span>@endif
    </label>
    <input
        id="{{ $name }}"
        type="{{ $type }}"
        wire:model="{{ $name }}"
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($required) required aria-required="true" @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600']) }}
    >
    @if ($hint)
        <p class="mt-1 text-xs text-gray-600">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $name }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
