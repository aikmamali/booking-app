@php use App\Modules\Communication\Enums\Channel; use App\Modules\Communication\Enums\NotificationType; @endphp
<section aria-labelledby="notifications-heading">
    <h2 id="notifications-heading" class="mb-1 text-lg font-semibold">{{ __('communication.preferences.title') }}</h2>
    <p class="mb-4 text-sm text-gray-700">{{ __('communication.preferences.intro') }}</p>
    <x-status :message="$status" />
    <form wire:submit="save" class="space-y-4">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="text-gray-600">
                    <th scope="col" class="py-1">{{ __('communication.preferences.type') }}</th>
                    @foreach (Channel::cases() as $channel)
                        <th scope="col" class="py-1 text-center">{{ $channel->label() }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach (NotificationType::cases() as $type)
                    <tr class="border-t border-gray-100">
                        <th scope="row" class="py-2 font-normal">{{ $type->label() }}</th>
                        @foreach (Channel::cases() as $channel)
                            <td class="py-2 text-center">
                                <input type="checkbox" class="h-4 w-4" wire:model="matrix.{{ $type->value }}.{{ $channel->value }}"
                                    aria-label="{{ $type->label() }}: {{ $channel->label() }}">
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
        <x-form.checkbox name="marketing">{{ __('communication.preferences.marketing') }}</x-form.checkbox>
        <x-form.button>{{ __('communication.preferences.save') }}</x-form.button>
    </form>
</section>
