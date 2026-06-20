<x-app-layout :title="__('Campaigns')">
    <x-slot:header>{{ __('Campaigns') }}</x-slot:header>

    @if (session('status'))
        <div class="mb-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700">{{ session('status') }}</div>
    @endif

    @php
        $dot = ['emerald' => 'bg-emerald-500', 'blue' => 'bg-blue-500', 'amber' => 'bg-amber-500', 'teal' => 'bg-teal-500', 'orange' => 'bg-orange-500', 'slate' => 'bg-slate-500'];
    @endphp

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div>
            @if ($campaigns->isEmpty())
                <div class="lf-card flex flex-col items-center justify-center px-6 py-16 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    </span>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">No campaigns yet</h3>
                    <p class="mt-1.5 max-w-sm text-sm text-slate-500">Group related links into a campaign to see their combined clicks and analytics. Create one on the right, then assign links to it.</p>
                </div>
            @else
                <div class="lf-card divide-y divide-slate-100">
                    @foreach ($campaigns as $c)
                        <div class="px-5 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $dot[$c->color] ?? 'bg-slate-400' }}"></span>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-slate-900">{{ $c->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $c->links_count }} {{ \Illuminate\Support\Str::plural('link', $c->links_count) }} · {{ number_format((int) $c->clicks_sum) }} clicks</p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <a href="{{ route('links.index', ['campaign' => $c->id]) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">Links</a>
                                    <a href="{{ route('campaigns.stats', $c) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">Analytics</a>
                                    <details class="relative">
                                        <summary class="flex cursor-pointer list-none items-center rounded-md p-1.5 text-slate-400 hover:bg-slate-100 [&::-webkit-details-marker]:hidden">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                        </summary>
                                        <div class="absolute right-0 z-10 mt-1 w-60 rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
                                            <form method="POST" action="{{ route('campaigns.update', $c) }}" class="space-y-2">
                                                @csrf @method('PUT')
                                                <input name="name" value="{{ $c->name }}" class="lf-input" maxlength="80">
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach ($colors as $col)
                                                        <label class="cursor-pointer">
                                                            <input type="radio" name="color" value="{{ $col }}" @checked($c->color === $col) class="peer sr-only">
                                                            <span class="block h-6 w-6 rounded-full {{ $dot[$col] }} ring-offset-2 peer-checked:ring-2 peer-checked:ring-slate-400"></span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                <button type="submit" class="w-full rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white">Save</button>
                                            </form>
                                            <form method="POST" action="{{ route('campaigns.destroy', $c) }}" data-confirm="Delete this campaign? Its links are kept." data-confirm-ok="Delete campaign" class="mt-2">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="w-full rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">Delete campaign</button>
                                            </form>
                                        </div>
                                    </details>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="lf-card h-fit p-6">
            <h3 class="text-sm font-semibold text-slate-900">New campaign</h3>
            <form method="POST" action="{{ route('campaigns.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="name" class="lf-label">Name</label>
                    <input id="name" name="name" value="{{ old('name') }}" class="lf-input" placeholder="e.g. Spring sale" maxlength="80">
                    @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <span class="lf-label">Colour</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($colors as $col)
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="{{ $col }}" @checked(old('color', 'emerald') === $col) class="peer sr-only">
                                <span class="block h-7 w-7 rounded-full {{ $dot[$col] }} ring-offset-2 peer-checked:ring-2 peer-checked:ring-slate-400"></span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <button type="submit" class="lf-btn">Create campaign</button>
            </form>
        </div>
    </div>
</x-app-layout>
