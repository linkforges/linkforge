<x-admin-layout title="Affiliate">
    <x-slot:header>Affiliate</x-slot:header>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>
    @endif

    @php $money = fn ($n) => $currency.' '.number_format((float) $n, 2); @endphp

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        @foreach ([['Pending', $totals['pending'], 'text-amber-600'], ['Approved', $totals['approved'], 'text-brand-700'], ['Paid out', $totals['paid'], 'text-emerald-600']] as [$label, $value, $color])
            <div class="lf-card p-4">
                <p class="text-xs font-medium tracking-wide text-slate-400 uppercase">{{ $label }}</p>
                <p class="mt-1 text-xl font-semibold {{ $color }}">{{ $money($value) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Payout requests --}}
    <div class="lf-card mb-6 overflow-hidden">
        <h3 class="border-b border-slate-100 px-5 py-3.5 text-sm font-semibold text-slate-900">Payout requests</h3>
        @if ($payouts->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-slate-400">No payout requests.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-400 uppercase">
                        <tr><th class="px-5 py-3 font-medium">Member</th><th class="px-5 py-3 font-medium">Amount</th><th class="px-5 py-3 font-medium">Method</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 text-right font-medium">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($payouts as $p)
                            @php $pb = ['pending' => 'bg-amber-50 text-amber-700', 'paid' => 'bg-emerald-50 text-emerald-700', 'rejected' => 'bg-slate-100 text-slate-500'][$p->status] ?? 'bg-slate-100 text-slate-500'; @endphp
                            <tr>
                                <td class="px-5 py-3 text-slate-600">{{ $p->user?->email ?? '—' }}</td>
                                <td class="px-5 py-3 font-medium text-slate-800">{{ $money($p->amount) }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ ucfirst($p->method) }}<div class="text-xs text-slate-400">{{ $p->details }}</div></td>
                                <td class="px-5 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $pb }}">{{ ucfirst($p->status) }}</span></td>
                                <td class="px-5 py-3 text-right">
                                    @if ($p->status === 'pending')
                                        <div class="inline-flex gap-1.5">
                                            <form method="POST" action="{{ route('admin.affiliate.payout', $p) }}">@csrf @method('PUT')<input type="hidden" name="status" value="paid"><button class="rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white">Mark paid</button></form>
                                            <form method="POST" action="{{ route('admin.affiliate.payout', $p) }}">@csrf @method('PUT')<input type="hidden" name="status" value="rejected"><button class="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600">Reject</button></form>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Commissions --}}
    <div class="lf-card overflow-hidden">
        <h3 class="border-b border-slate-100 px-5 py-3.5 text-sm font-semibold text-slate-900">Commissions</h3>
        @if ($commissions->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-slate-400">No commissions recorded yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-400 uppercase">
                        <tr><th class="px-5 py-3 font-medium">Date</th><th class="px-5 py-3 font-medium">Referrer</th><th class="px-5 py-3 font-medium">Referred</th><th class="px-5 py-3 font-medium">Amount</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 text-right font-medium">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($commissions as $c)
                            @php $badge = ['pending' => 'bg-amber-50 text-amber-700', 'approved' => 'bg-brand-50 text-brand-700', 'paid' => 'bg-emerald-50 text-emerald-700', 'rejected' => 'bg-slate-100 text-slate-500'][$c->status] ?? 'bg-slate-100 text-slate-500'; @endphp
                            <tr>
                                <td class="px-5 py-3 text-slate-500">{{ $c->created_at->format('M j, Y') }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $c->referrer?->email ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $c->referredUser?->email ?? '—' }}</td>
                                <td class="px-5 py-3 font-medium text-slate-800">{{ $c->currency }} {{ number_format((float) $c->amount, 2) }}</td>
                                <td class="px-5 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ ucfirst($c->status) }}</span></td>
                                <td class="px-5 py-3 text-right">
                                    @if (in_array($c->status, ['pending', 'approved'], true))
                                        <div class="inline-flex gap-1.5">
                                            @if ($c->status === 'pending')
                                                <form method="POST" action="{{ route('admin.affiliate.commission', $c) }}">@csrf @method('PUT')<input type="hidden" name="status" value="approved"><button class="rounded-md bg-brand-600 px-2.5 py-1 text-xs font-semibold text-white">Approve</button></form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.affiliate.commission', $c) }}">@csrf @method('PUT')<input type="hidden" name="status" value="rejected"><button class="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600">Reject</button></form>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $commissions->links() }}</div>
        @endif
    </div>
</x-admin-layout>
