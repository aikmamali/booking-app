<section class="mx-auto max-w-md">
    <h1 class="mb-2 text-2xl font-semibold">{{ __('identity.verify.title') }}</h1>
    <p class="mb-6 text-gray-700">{{ __('identity.verify.intro', ['email' => auth()->user()?->email]) }}</p>
    <x-status :message="$status" />
    <form wire:submit="resend">
        <x-form.button>{{ __('identity.verify.resend') }}</x-form.button>
    </form>
</section>
