<section class="mx-auto max-w-sm space-y-4 text-center">
    <h1 class="text-2xl font-semibold">{{ __('identity.card.title') }}</h1>
    <x-status :message="$status" />
    <div class="rounded-xl border border-gray-200 p-6 shadow-sm">
        <p class="text-lg font-semibold">{{ $user->fullName() }}</p>
        <div class="mx-auto mt-4 w-60" role="img" aria-label="{{ __('identity.card.qr_label') }}">{!! $qr !!}</div>
        <p class="mt-4 text-sm text-gray-700">{{ __('identity.card.hint') }}</p>
    </div>
    @if ($confirmingReissue)
        <div class="rounded-md border border-amber-300 bg-amber-50 p-3 text-left text-sm">
            <p>{{ __('identity.card.reissue_warning') }}</p>
            <div class="mt-3 flex gap-2">
                <button type="button" wire:click="reissue" class="rounded-md bg-teal-700 px-3 py-2 font-semibold text-white">{{ __('identity.card.reissue_confirm') }}</button>
                <button type="button" wire:click="$set('confirmingReissue', false)" class="rounded-md border px-3 py-2">{{ __('booking.book.back') }}</button>
            </div>
        </div>
    @else
        <button type="button" wire:click="$set('confirmingReissue', true)" class="text-sm underline">{{ __('identity.card.reissue') }}</button>
    @endif
</section>
