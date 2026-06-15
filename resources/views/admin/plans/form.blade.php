@php $editing = (bool) $plan->exists; @endphp
<x-admin-layout :title="$editing ? 'Edit plan' : 'New plan'">
    <x-slot:header>{{ $editing ? 'Edit plan: '.$plan->name : 'New plan' }}</x-slot:header>

    <div class="mb-5">
        <a href="{{ route('admin.plans') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            All plans
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
            Please fix the errors below.
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="mx-auto max-w-3xl space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- Identity & price --}}
        <div class="lf-card p-6">
            <h3 class="mb-4 text-sm font-semibold text-slate-900">Plan details</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="lf-label" for="name">Name</label>
                    <input id="name" name="name" value="{{ old('name', $plan->name) }}" class="lf-input" required>
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="lf-label" for="slug">Slug</label>
                    <input id="slug" name="slug" value="{{ old('slug', $plan->slug) }}" class="lf-input" placeholder="pro" required>
                    @error('slug')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="lf-label" for="price">Price</label>
                    <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $plan->price ?? 0) }}" class="lf-input" required>
                    @error('price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="lf-label" for="currency">Currency</label>
                        <input id="currency" name="currency" value="{{ old('currency', $plan->currency ?? 'USD') }}" maxlength="3" class="lf-input uppercase" required>
                        @error('currency')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="lf-label" for="interval">Billing</label>
                        <select id="interval" name="interval" class="lf-input">
                            @foreach ($intervals as $val => $label)
                                <option value="{{ $val }}" @selected(old('interval', $plan->interval) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="lf-label" for="sort">Display order</label>
                    <input id="sort" name="sort" type="number" min="0" value="{{ old('sort', $plan->sort ?? 0) }}" class="lf-input">
                </div>
                <label class="flex items-center gap-2 self-end pb-2.5 text-sm text-slate-600">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active ?? true))
                           class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                    Active (shown on pricing page)
                </label>
            </div>
        </div>

        {{-- Limits --}}
        <div class="lf-card p-6">
            <h3 class="text-sm font-semibold text-slate-900">Limits</h3>
            <p class="mb-4 text-xs text-slate-400">Set a number, or tick Unlimited to remove the cap.</p>
            <div class="space-y-3">
                @foreach ($limits as $key => $label)
                    @php $val = old("limits.$key", $plan->limit($key)); $isUnlimited = old("unlimited.$key", $val === null); @endphp
                    <div class="grid grid-cols-[1fr_auto_auto] items-center gap-3" data-limit-row>
                        <label class="text-sm font-medium text-slate-700" for="limit_{{ $key }}">{{ $label }}</label>
                        <input id="limit_{{ $key }}" name="limits[{{ $key }}]" type="number" min="0" value="{{ $isUnlimited ? '' : $val }}"
                               class="lf-input !w-36 {{ $isUnlimited ? 'opacity-40' : '' }}" data-limit-input @disabled($isUnlimited)>
                        <label class="flex items-center gap-1.5 text-xs text-slate-500">
                            <input type="checkbox" name="unlimited[{{ $key }}]" value="1" @checked($isUnlimited)
                                   class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30" data-unlimited-toggle>
                            Unlimited
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Features --}}
        <div class="lf-card p-6">
            <h3 class="mb-4 text-sm font-semibold text-slate-900">Features</h3>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($features as $key => $label)
                    <label class="flex items-center gap-2.5 rounded-lg border border-slate-200 px-3.5 py-3 text-sm text-slate-700">
                        <input type="checkbox" name="features[{{ $key }}]" value="1" @checked(old("features.$key", $plan->allows($key)))
                               class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.plans') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</a>
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                {{ $editing ? 'Save plan' : 'Create plan' }}
            </button>
        </div>
    </form>

    <script>
        // "Unlimited" disables/greys the matching number input.
        document.querySelectorAll('[data-limit-row]').forEach(function (row) {
            var toggle = row.querySelector('[data-unlimited-toggle]');
            var input = row.querySelector('[data-limit-input]');
            toggle.addEventListener('change', function () {
                input.disabled = toggle.checked;
                input.classList.toggle('opacity-40', toggle.checked);
                if (!toggle.checked) input.focus();
            });
        });
    </script>
</x-admin-layout>
