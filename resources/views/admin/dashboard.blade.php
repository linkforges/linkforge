<x-admin-layout title="Dashboard">
    <x-slot:header>Admin overview</x-slot:header>

    {{-- Headline stats --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $cards = [
                ['Users', number_format($stats['users'])],
                ['MRR', $currency.' '.number_format($stats['mrr'], 2)],
                ['Total clicks', number_format($stats['clicks'])],
                ['Links', number_format($stats['links'])],
            ];
        @endphp
        @foreach ($cards as [$label, $val])
            <div class="lf-card p-5">
                <span class="text-sm font-medium text-slate-500">{{ $label }}</span>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $val }}</p>
            </div>
        @endforeach
    </div>

    @if ($stats['open_reports'] > 0)
        <a href="{{ route('admin.reports') }}" class="mt-5 flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 transition hover:bg-amber-100">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01"/></svg>
            <span><strong>{{ $stats['open_reports'] }}</strong> open abuse {{ \Illuminate\Support\Str::plural('report', $stats['open_reports']) }} need review.</span>
        </a>
    @endif

    {{-- Trends --}}
    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        <div class="lf-card p-5">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">New signups <span class="font-normal text-slate-400">· last 30 days</span></h3>
            @include('analytics.partials.area-chart', ['series' => $userSeries])
        </div>
        <div class="lf-card p-5">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Clicks <span class="font-normal text-slate-400">· last 30 days</span></h3>
            @include('analytics.partials.area-chart', ['series' => $clickSeries])
        </div>
    </div>

    {{-- Plan mix + top links --}}
    <div class="mt-6 grid gap-5 lg:grid-cols-3">
        <div class="lf-card p-5">
            <h3 class="mb-4 text-sm font-semibold text-slate-900">Plan distribution</h3>
            @include('analytics.partials.pie', ['slices' => $planSlices, 'size' => 150])
            <div class="mt-4 space-y-1.5">
                @forelse ($planSlices as $slice)
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full" style="background:{{ $slice['color'] }}"></span>{{ $slice['label'] }}</span>
                        <span class="font-medium text-slate-800">{{ number_format($slice['value']) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No users yet.</p>
                @endforelse
            </div>
        </div>

        <div class="lf-card overflow-hidden lg:col-span-2">
            <h3 class="border-b border-slate-100 px-5 py-4 text-sm font-semibold text-slate-900">Top links</h3>
            <div class="divide-y divide-slate-100">
                @forelse ($topLinks as $link)
                    <div class="flex items-center justify-between px-5 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-900">/{{ $link->alias }}</p>
                            <p class="truncate text-xs text-slate-400">{{ $link->user?->email ?? 'unknown' }}</p>
                        </div>
                        <span class="shrink-0 text-sm font-semibold text-slate-700">{{ number_format($link->clicks) }} <span class="font-normal text-slate-400">clicks</span></span>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-400">No links yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent signups --}}
    <div class="lf-card mt-6">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Recent signups</h3>
            <a href="{{ route('admin.users') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">All users</a>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($recentUsers as $u)
                <a href="{{ route('admin.users.show', $u) }}" class="flex items-center justify-between px-5 py-3 transition hover:bg-slate-50/50">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-slate-900">{{ $u->name }}</p>
                        <p class="truncate text-xs text-slate-400">{{ $u->email }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-sm text-slate-600">{{ $u->plan?->name ?? 'Free' }}</p>
                        <p class="text-xs text-slate-400">{{ $u->created_at?->diffForHumans() }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-admin-layout>
