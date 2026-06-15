<x-admin-layout title="Plans">
    <x-slot:header>Plans &amp; pricing</x-slot:header>

    @if (session('status'))<div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif

    <div class="mb-5 flex items-center justify-between">
        <p class="text-sm text-slate-500">Define the pricing tiers, limits and feature flags your customers can subscribe to.</p>
        <a href="{{ route('admin.plans.create') }}" class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            New plan
        </a>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($plans as $plan)
            <div class="lf-card flex flex-col p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $plan->name }}</p>
                        <p class="text-xs text-slate-400">{{ $plan->slug }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $plan->is_active ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-500' }}">
                        {{ $plan->is_active ? 'Active' : 'Hidden' }}
                    </span>
                </div>

                <p class="mt-4">
                    <span class="text-3xl font-semibold tracking-tight text-slate-900">{{ $plan->price > 0 ? $plan->currency.' '.rtrim(rtrim(number_format($plan->price, 2), '0'), '.') : 'Free' }}</span>
                    @if ($plan->price > 0 && $plan->interval !== 'lifetime')<span class="text-sm text-slate-400">/ {{ $plan->interval }}</span>@endif
                    @if ($plan->interval === 'lifetime')<span class="text-sm text-slate-400">one time</span>@endif
                </p>

                <dl class="mt-4 space-y-1.5 text-sm text-slate-600">
                    @foreach (\App\Http\Controllers\Admin\PlanController::LIMITS as $key => $label)
                        @php $v = $plan->limit($key); @endphp
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="font-medium text-slate-800">{{ $v === null ? 'Unlimited' : number_format((int) $v) }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div class="mt-4 flex flex-wrap gap-1.5">
                    @foreach (\App\Http\Controllers\Admin\PlanController::FEATURES as $key => $label)
                        @if ($plan->allows($key))
                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ $label }}</span>
                        @endif
                    @endforeach
                </div>

                <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                    <span class="text-xs text-slate-400">{{ number_format($plan->users_count) }} {{ \Illuminate\Support\Str::plural('user', $plan->users_count) }}</span>
                    <div class="flex items-center gap-1.5">
                        <a href="{{ route('admin.plans.edit', $plan) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">Edit</a>
                        @if ($plan->slug !== 'free')
                            <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" data-confirm="Delete the {{ $plan->name }} plan?" data-confirm-ok="Delete plan">
                                @csrf @method('DELETE')
                                <button type="submit" class="rounded-md p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Delete">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-admin-layout>
