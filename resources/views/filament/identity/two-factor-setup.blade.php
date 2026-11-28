<x-filament-panels::page.simple>
    @if ($recoveryCodes !== [])
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('identity.two_factor.recovery_codes_intro') }}</p>
            <ul class="grid grid-cols-2 gap-2 rounded-lg bg-gray-50 p-4 font-mono text-sm dark:bg-white/5" data-recovery-codes>
                @foreach ($recoveryCodes as $code)
                    <li>{{ $code }}</li>
                @endforeach
            </ul>
            <x-filament::button tag="a" :href="filament()->getUrl()" class="w-full">
                {{ __('identity.two_factor.continue') }}
            </x-filament::button>
        </div>
    @elseif ($this->isEnabled())
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('identity.two_factor.enabled_intro') }}</p>
            <x-filament::button wire:click="regenerate" color="gray" class="w-full">
                {{ __('identity.two_factor.regenerate_recovery_codes') }}
            </x-filament::button>
            <x-filament::link :href="filament()->getUrl()">{{ __('identity.two_factor.back') }}</x-filament::link>
        </div>
    @else
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('identity.two_factor.setup_intro') }}</p>
            <div class="flex justify-center rounded-lg bg-white p-3">{!! $this->qrSvg() !!}</div>
            <p class="text-center text-xs text-gray-500">
                {{ __('identity.two_factor.manual_key') }}
                <code class="break-all font-mono" data-secret>{{ $this->secret() }}</code>
            </p>
            <x-filament-panels::form wire:submit="confirm">
                {{ $this->form }}
                <x-filament::button type="submit" class="w-full">
                    {{ __('identity.two_factor.enable') }}
                </x-filament::button>
            </x-filament-panels::form>
        </div>
    @endif
</x-filament-panels::page.simple>
