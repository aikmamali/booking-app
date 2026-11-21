<section class="mx-auto max-w-md">
    <h1 class="mb-6 text-2xl font-semibold">{{ __('identity.register.title') }}</h1>
    <form wire:submit="register" class="space-y-4" novalidate>
        <x-form.input name="first_name" :label="__('identity.fields.first_name')" autocomplete="given-name" required />
        <x-form.input name="last_name" :label="__('identity.fields.last_name')" autocomplete="family-name" required />
        <x-form.input name="email" type="email" :label="__('identity.fields.email')" autocomplete="email" required />
        <x-form.input name="phone" type="tel" :label="__('identity.fields.phone')" autocomplete="tel" :hint="__('identity.fields.phone_hint')" required />
        <x-form.input name="password" type="password" :label="__('identity.fields.password')" autocomplete="new-password" :hint="__('identity.fields.password_hint')" required />
        <x-form.input name="password_confirmation" type="password" :label="__('identity.fields.password_confirmation')" autocomplete="new-password" required />
        <x-form.checkbox name="accept_terms">
            {!! __('identity.register.accept_terms', [
                'terms' => '<a href="'.e(route('app.terms')).'" class="underline" target="_blank">'.e(__('identity.register.terms_link')).'</a>',
                'privacy' => '<a href="'.e(route('app.privacy')).'" class="underline" target="_blank">'.e(__('identity.register.privacy_link')).'</a>',
            ]) !!}
        </x-form.checkbox>
        <x-form.checkbox name="marketing_consent">{{ __('identity.register.marketing_consent') }}</x-form.checkbox>
        <x-form.button>{{ __('identity.register.submit') }}</x-form.button>
    </form>
    <p class="mt-6 text-sm">{{ __('identity.register.has_account') }} <a href="{{ route('app.login') }}" class="underline">{{ __('identity.login.title') }}</a></p>
</section>
