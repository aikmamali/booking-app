<x-layouts.app :title="__('identity.legal.terms')">
    <article class="space-y-4">
        <h1 class="text-2xl font-semibold">{{ __('identity.legal.terms') }}</h1>
        <p class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900" role="note">{{ __('identity.legal.draft_notice') }}</p>
        <p class="text-sm text-gray-600">{{ __('identity.legal.version', ['version' => app(\App\Modules\Identity\Settings\IdentitySettings::class)->terms_version]) }}</p>
        <p>{{ __('identity.legal.terms_body') }}</p>
    </article>
</x-layouts.app>
