@php use App\View\Format; use App\Modules\Membership\Enums\MembershipStatus; @endphp
<section class="space-y-6">
    <h1 class="text-2xl font-semibold">{{ __('membership.mine.title') }}</h1>

    @forelse ($memberships as $membership)
        @php($balance = $balances[$membership->id])
        <article class="rounded-md border border-gray-200 p-4" aria-labelledby="membership-{{ $membership->public_id }}">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <h2 id="membership-{{ $membership->public_id }}" class="text-lg font-semibold">{{ $membership->package_name }}</h2>
                <span @class([
                    'rounded-full px-2 py-0.5 text-sm',
                    'bg-teal-100 text-teal-900' => $membership->status === MembershipStatus::Active,
                    'bg-gray-100 text-gray-800' => $membership->status !== MembershipStatus::Active,
                ])>{{ $membership->status->label() }}</span>
            </div>
            @if ($membership->status === MembershipStatus::Frozen && $membership->frozen_until !== null)
                <p class="mt-1 text-sm text-gray-700">{{ __('membership.mine.frozen_until', ['date' => Format::date($membership->frozen_until)]) }}</p>
            @endif

            <dl class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-gray-600">{{ __('membership.fields.balance') }}</dt>
                    <dd class="text-2xl font-semibold">{{ Format::credits($balance) }} <span class="text-sm font-normal">/ {{ Format::credits($membership->credits) }}</span></dd>
                </div>
                <div>
                    <dt class="text-gray-600">{{ __('membership.fields.period') }}</dt>
                    <dd>{{ Format::date($membership->starts_on) }} – {{ Format::date($membership->ends_on) }}</dd>
                </div>
            </dl>

            <details class="mt-4">
                <summary class="cursor-pointer text-sm underline">{{ __('membership.ledger.title') }}</summary>
                <table class="mt-2 w-full text-left text-sm">
                    <thead class="text-gray-600">
                        <tr>
                            <th scope="col" class="py-1">{{ __('membership.ledger.when') }}</th>
                            <th scope="col" class="py-1">{{ __('membership.ledger.type') }}</th>
                            <th scope="col" class="py-1 text-right">{{ __('membership.ledger.amount') }}</th>
                            <th scope="col" class="py-1 text-right">{{ __('membership.ledger.balance_after') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($membership->transactions as $entry)
                            <tr class="border-t border-gray-100">
                                <td class="py-1">{{ $entry->created_at->setTimezone(app(\App\Shared\BusinessTime::class)->timezone())->format('d/m/Y H:i') }}</td>
                                <td class="py-1">{{ $entry->type->label() }}</td>
                                <td class="py-1 text-right">{{ ($entry->amount->isPositive() ? '+' : '').Format::credits($entry->amount) }}</td>
                                <td class="py-1 text-right">{{ Format::credits($entry->balance_after) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </details>
        </article>
    @empty
        <p class="text-gray-700">{{ __('membership.mine.empty') }}</p>
    @endforelse
</section>
