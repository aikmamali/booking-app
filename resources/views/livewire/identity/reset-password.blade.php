<section class="mx-auto max-w-md">
    <h1 class="mb-6 text-2xl font-semibold">{{ $heading }}</h1>
    <form wire:submit="save" class="space-y-4" novalidate>
        <x-form.input name="email" type="email" :label="__('identity.fields.email')" autocomplete="email" required />
        <x-form.input name="password" type="password" :label="__('identity.fields.new_password')" autocomplete="new-password" :hint="__('identity.fields.password_hint')" required />
        <x-form.input name="password_confirmation" type="password" :label="__('identity.fields.password_confirmation')" autocomplete="new-password" required />
        <x-form.button>{{ __('identity.reset.submit') }}</x-form.button>
    </form>
</section>
