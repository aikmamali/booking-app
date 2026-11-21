<section aria-labelledby="profile-heading">
    <h2 id="profile-heading" class="mb-4 text-lg font-semibold">{{ __('identity.profile.details') }}</h2>
    <form wire:submit="save" class="space-y-4" novalidate>
        <x-form.input name="first_name" :label="__('identity.fields.first_name')" autocomplete="given-name" required />
        <x-form.input name="last_name" :label="__('identity.fields.last_name')" autocomplete="family-name" required />
        <x-form.input name="email" type="email" :label="__('identity.fields.email')" autocomplete="email" :hint="__('identity.profile.email_hint')" required />
        <x-form.input name="phone" type="tel" :label="__('identity.fields.phone')" autocomplete="tel" :hint="__('identity.fields.phone_hint')" required />
        <x-form.input name="phone_alt" type="tel" :label="__('identity.fields.phone_alt')" />
        <x-form.input name="vat_number" :label="__('identity.fields.vat_number')" inputmode="numeric" :hint="__('identity.fields.vat_number_hint')" />
        <div>
            <label for="locale" class="block text-sm font-medium">{{ __('identity.fields.locale') }}</label>
            <select id="locale" wire:model="locale" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2">
                @foreach (\App\Modules\Identity\Enums\Locale::options() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('locale')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <x-form.button>{{ __('identity.profile.save') }}</x-form.button>
    </form>
</section>
