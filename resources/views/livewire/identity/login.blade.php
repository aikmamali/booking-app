<section class="mx-auto max-w-md">
    <h1 class="mb-6 text-2xl font-semibold">{{ __('identity.login.title') }}</h1>
    <x-status />
    <form wire:submit="login" class="space-y-4" novalidate>
        <x-form.input name="email" type="email" :label="__('identity.fields.email')" autocomplete="email" required />
        <x-form.input name="password" type="password" :label="__('identity.fields.password')" autocomplete="current-password" required />
        <x-form.checkbox name="remember">{{ __('identity.login.remember') }}</x-form.checkbox>
        <x-form.button>{{ __('identity.login.submit') }}</x-form.button>
    </form>
    <div class="mt-6 space-y-2 text-sm">
        <p><a href="{{ route('app.password.request') }}" class="underline">{{ __('identity.forgot.link') }}</a></p>
        <p>{{ __('identity.login.no_account') }} <a href="{{ route('app.register') }}" class="underline">{{ __('identity.register.title') }}</a></p>
    </div>
</section>
