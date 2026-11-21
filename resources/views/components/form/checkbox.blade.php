@props(['name'])

<div>
    <label class="flex items-start gap-2 text-sm">
        <input type="checkbox" id="{{ $name }}" wire:model="{{ $name }}" class="mt-1 h-4 w-4 rounded border-gray-300"
            @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
        <span>{{ $slot }}</span>
    </label>
    @error($name)
        <p id="{{ $name }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
