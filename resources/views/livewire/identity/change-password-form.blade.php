<section aria-labelledby="password-heading">
    <h2 id="password-heading" class="mb-4 text-lg font-semibold">{{ __('identity.password.title') }}</h2>
    <x-status :message="$status" />
    <form wire:submit="save" class="space-y-4" novalidate>
        <x-form.input name="current_password" type="password" :label="__('identity.fields.current_password')" autocomplete="current-password" required />
        <x-form.input name="password" type="password" :label="__('identity.fields.new_password')" autocomplete="new-password" :hint="__('identity.fields.password_hint')" required />
        <x-form.input name="password_confirmation" type="password" :label="__('identity.fields.password_confirmation')" autocomplete="new-password" required />
        <x-form.button>{{ __('identity.password.submit') }}</x-form.button>
    </form>
</section>
