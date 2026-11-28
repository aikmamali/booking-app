<x-filament-panels::page.simple>
    <x-filament-panels::form wire:submit="verify">
        {{ $this->form }}

        <x-filament::button type="submit" class="w-full">
            {{ __('identity.two_factor.verify') }}
        </x-filament::button>
    </x-filament-panels::form>
</x-filament-panels::page.simple>
