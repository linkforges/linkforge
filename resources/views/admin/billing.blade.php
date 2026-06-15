<x-admin-layout title="Billing">
    <x-slot:header>Billing &amp; revenue</x-slot:header>

    @if (session('status'))<div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>@endif

    @php
        $money = fn ($v) => $currency.' '.number_format((float) $v, 2);
        $subBadge = ['trialing' => 'bg-sky-50 text-sky-700', 'active' => 'bg-brand-50 text-brand-700', 'past_due' => 'bg-amber-50 text-amber-700', 'canceled' => 'bg-slate-100 text-slate-500', 'expired' => 'bg-red-50 text-red-700'];
        $payBadge = ['pending' => 'bg-amber-50 text-amber-700', 'completed' => 'bg-brand-50 text-brand-700', 'failed' => 'bg-red-50 text-red-700', 'refunded' => 'bg-slate-100 text-slate-500'];
    @endphp

    {{-- Stats --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <div class="lf-card p-5">
            <span class="text-sm font-medium text-slate-500">MRR</span>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $money($stats['mrr']) }}</p>
            <p class="mt-1 text-xs text-slate-400">Monthly recurring revenue</p>
        </div>
        <div class="lf-card p-5">
            <span class="text-sm font-medium text-slate-500">Revenue this month</span>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $money($stats['revenue_month']) }}</p>
            <p class="mt-1 text-xs text-slate-400">Completed payments since the 1st</p>
        </div>
        <div class="lf-card p-5">
            <span class="text-sm font-medium text-slate-500">Total revenue</span>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $money($stats['revenue_total']) }}</p>
            <p class="mt-1 text-xs text-slate-400">All completed payments</p>
        </div>
        <div class="lf-card p-5">
            <span class="text-sm font-medium text-slate-500">Active subscriptions</span>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ number_format($stats['active_subs']) }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ number_format($stats['paying_users']) }} paying {{ \Illuminate\Support\Str::plural('user', $stats['paying_users']) }}</p>
        </div>
    </div>

    {{-- Subscriptions --}}
    <div class="mt-8">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Subscriptions</h3>
            <div class="flex flex-wrap gap-1.5 text-xs">
                <a href="{{ route('admin.billing', array_merge(request()->query(), ['sub_status' => null, 'subs' => 1])) }}" @class(['rounded-md px-2.5 py-1 font-medium', 'bg-slate-900 text-white' => ! $subStatus, 'bg-slate-100 text-slate-600 hover:bg-slate-200' => $subStatus])>All</a>
                @foreach ($subStatuses as $st)
                    <a href="{{ route('admin.billing', array_merge(request()->query(), ['sub_status' => $st, 'subs' => 1])) }}" @class(['rounded-md px-2.5 py-1 font-medium capitalize', 'bg-slate-900 text-white' => $subStatus === $st, 'bg-slate-100 text-slate-600 hover:bg-slate-200' => $subStatus !== $st])>{{ str_replace('_', ' ', $st) }}</a>
                @endforeach
            </div>
        </div>
        <div class="lf-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-400 uppercase">
                        <tr>
                            <th class="px-5 py-3 font-medium">User</th>
                            <th class="px-5 py-3 font-medium">Plan</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium">Gateway</th>
                            <th class="px-5 py-3 font-medium">Renews</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($subscriptions as $sub)
                            <tr>
                                <td class="px-5 py-3 text-slate-700">{{ $sub->user?->email ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $sub->plan?->name ?? '—' }}</td>
                                <td class="px-5 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $subBadge[$sub->status] ?? 'bg-slate-100 text-slate-500' }}">{{ str_replace('_', ' ', ucfirst($sub->status)) }}</span></td>
                                <td class="px-5 py-3 text-slate-500 capitalize">{{ $sub->gateway }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $sub->renews_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-5 py-3 text-right">
                                    @if ($sub->isActive())
                                        <form method="POST" action="{{ route('admin.billing.subscriptions.update', $sub) }}" data-confirm="Cancel this subscription and move the user to Free?" data-confirm-ok="Cancel subscription">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600 transition hover:bg-red-50 hover:text-red-600">Cancel</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-slate-400">No subscriptions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $subscriptions->links() }}</div>
    </div>

    {{-- Transactions --}}
    <div class="mt-8">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Transactions</h3>
            <div class="flex flex-wrap gap-1.5 text-xs">
                <a href="{{ route('admin.billing', array_merge(request()->query(), ['pay_status' => null, 'pay' => 1])) }}" @class(['rounded-md px-2.5 py-1 font-medium', 'bg-slate-900 text-white' => ! $payStatus, 'bg-slate-100 text-slate-600 hover:bg-slate-200' => $payStatus])>All</a>
                @foreach ($payStatuses as $st)
                    <a href="{{ route('admin.billing', array_merge(request()->query(), ['pay_status' => $st, 'pay' => 1])) }}" @class(['rounded-md px-2.5 py-1 font-medium capitalize', 'bg-slate-900 text-white' => $payStatus === $st, 'bg-slate-100 text-slate-600 hover:bg-slate-200' => $payStatus !== $st])>{{ $st }}</a>
                @endforeach
            </div>
        </div>
        <div class="lf-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-400 uppercase">
                        <tr>
                            <th class="px-5 py-3 font-medium">User</th>
                            <th class="px-5 py-3 font-medium">Plan</th>
                            <th class="px-5 py-3 font-medium">Amount</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium">Date</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="px-5 py-3 text-slate-700">{{ $payment->user?->email ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $payment->plan?->name ?? '—' }}</td>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="px-5 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $payBadge[$payment->status] ?? 'bg-slate-100 text-slate-500' }}">{{ ucfirst($payment->status) }}</span></td>
                                <td class="px-5 py-3 text-slate-500">{{ $payment->created_at?->format('M j, Y') }}</td>
                                <td class="px-5 py-3 text-right">
                                    @if ($payment->status === 'completed')
                                        <form method="POST" action="{{ route('admin.billing.payments.update', $payment) }}" data-confirm="Mark this payment as refunded? This records the refund only; issue the actual refund in your gateway." data-confirm-ok="Mark refunded">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="action" value="refund">
                                            <button type="submit" class="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-50">Refund</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-slate-400">No transactions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $payments->links() }}</div>
    </div>
</x-admin-layout>
