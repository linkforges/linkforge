<x-app-layout title="Affiliate">
    <x-slot:header>Affiliate</x-slot:header>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>
    @endif

    @php $cur = $settings['currency']; $money = fn ($n) => $cur.' '.number_format((float) $n, 2); @endphp

    {{-- Referral link --}}
    <div class="lf-card p-6">
        <h3 class="text-sm font-semibold text-slate-900">Your referral link</h3>
        <p class="mt-1 text-sm text-slate-500">
            Earn {{ $settings['type'] === 'percent' ? rtrim(rtrim(number_format($settings['value'], 2), '0'), '.').'%' : $money($settings['value']) }}
            when someone you refer upgrades to a paid plan.
        </p>
        <div class="mt-3 flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-2">
            <input id="ref-url" readonly value="{{ $url }}" class="min-w-0 flex-1 bg-transparent px-2 text-sm text-slate-700 outline-none">
            <button type="button" id="ref-copy" class="shrink-0 rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white">Copy</button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="mt-5 grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ([
            ['Clicks', number_format($clicks), 'text-slate-900'],
            ['Signups', number_format($signups), 'text-slate-900'],
            ['Pending', $money($pending), 'text-amber-600'],
            ['Approved', $money($approved), 'text-brand-700'],
            ['Paid out', $money($paid), 'text-emerald-600'],
        ] as [$label, $value, $color])
            <div class="lf-card p-4">
                <p class="text-xs font-medium tracking-wide text-slate-400 uppercase">{{ $label }}</p>
                <p class="mt-1 text-xl font-semibold {{ $color }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_320px]">
        {{-- Commissions --}}
        <div class="lf-card overflow-hidden">
            <h3 class="border-b border-slate-100 px-5 py-3.5 text-sm font-semibold text-slate-900">Commissions</h3>
            @if ($commissions->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-slate-400">No commissions yet. Share your link to start earning.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-400 uppercase">
                            <tr><th class="px-5 py-3 font-medium">Date</th><th class="px-5 py-3 font-medium">Referred</th><th class="px-5 py-3 font-medium">Amount</th><th class="px-5 py-3 font-medium">Status</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($commissions as $c)
                                @php $badge = ['pending' => 'bg-amber-50 text-amber-700', 'approved' => 'bg-brand-50 text-brand-700', 'paid' => 'bg-emerald-50 text-emerald-700', 'rejected' => 'bg-slate-100 text-slate-500'][$c->status] ?? 'bg-slate-100 text-slate-500'; @endphp
                                <tr>
                                    <td class="px-5 py-3 text-slate-500">{{ $c->created_at->format('M j, Y') }}</td>
                                    <td class="px-5 py-3 text-slate-600">{{ $c->referredUser?->email ?? '—' }}</td>
                                    <td class="px-5 py-3 font-medium text-slate-800">{{ $cur }} {{ number_format((float) $c->amount, 2) }}</td>
                                    <td class="px-5 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ ucfirst($c->status) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Payout --}}
        <div class="space-y-5">
            <div class="lf-card p-5">
                <h3 class="text-sm font-semibold text-slate-900">Request a payout</h3>
                <p class="mt-1 text-sm text-slate-500">Available: <span class="font-semibold text-slate-800">{{ $money($payable) }}</span></p>
                <p class="text-xs text-slate-400">Minimum payout {{ $money($settings['min_payout']) }}.</p>
                <form method="POST" action="{{ route('affiliate.payout') }}" class="mt-3 space-y-3">
                    @csrf
                    <select name="method" class="lf-input">
                        <option value="paypal">PayPal</option>
                        <option value="bank">Bank transfer</option>
                        <option value="crypto">Crypto</option>
                    </select>
                    <input name="details" class="lf-input" placeholder="PayPal email / account details" maxlength="255" required>
                    <button type="submit" @disabled($payable < $settings['min_payout']) class="lf-btn w-full disabled:cursor-not-allowed disabled:opacity-50">Request payout</button>
                </form>
            </div>

            @if ($payouts->isNotEmpty())
                <div class="lf-card p-5">
                    <h3 class="mb-3 text-sm font-semibold text-slate-900">Payout history</h3>
                    <ul class="space-y-2 text-sm">
                        @foreach ($payouts as $p)
                            @php $pb = ['pending' => 'text-amber-600', 'paid' => 'text-emerald-600', 'rejected' => 'text-slate-400'][$p->status] ?? 'text-slate-500'; @endphp
                            <li class="flex items-center justify-between">
                                <span class="text-slate-600">{{ $p->created_at->format('M j') }} · {{ ucfirst($p->method) }}</span>
                                <span class="font-medium text-slate-800">{{ $cur }} {{ number_format((float) $p->amount, 2) }}</span>
                                <span class="text-xs font-medium {{ $pb }}">{{ ucfirst($p->status) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.getElementById('ref-copy')?.addEventListener('click', function () {
            navigator.clipboard.writeText(document.getElementById('ref-url').value);
            this.textContent = 'Copied'; setTimeout(() => { this.textContent = 'Copy'; }, 1200);
        });
    </script>
</x-app-layout>
