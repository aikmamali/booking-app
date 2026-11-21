<section class="mx-auto max-w-md">
    <h1 class="mb-2 text-2xl font-semibold">{{ __('identity.forgot.title') }}</h1>
    <p class="mb-6 text-sm text-gray-700">{{ __('identity.forgot.intro') }}</p>
    <x-status :message="$status" />
    <form wire:submit="send" class="space-y-4" novalidate>
        <x-form.input name="email" type="email" :label="__('identity.fields.email')" autocomplete="email" required />
        <x-form.button>{{ __('identity.forgot.submit') }}</x-form.button>
    </form>
    <p class="mt-6 text-sm"><a href="{{ route('app.login') }}" class="underline">{{ __('identity.login.back') }}</a></p>
</section>
