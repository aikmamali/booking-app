<section aria-labelledby="delete-heading" class="rounded-md border border-red-200 p-4">
    <h2 id="delete-heading" class="mb-2 text-lg font-semibold text-red-800">{{ __('identity.delete.title') }}</h2>
    <p class="mb-4 text-sm text-gray-700">{{ __('identity.delete.intro') }}</p>
    <form wire:submit="delete" class="space-y-4" novalidate>
        <x-form.input name="password" type="password" :label="__('identity.fields.password')" autocomplete="current-password" required />
        <x-form.checkbox name="confirm">{{ __('identity.delete.confirm') }}</x-form.checkbox>
        <x-form.button variant="danger">{{ __('identity.delete.submit') }}</x-form.button>
    </form>
</section>
