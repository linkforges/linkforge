<div class="grid gap-6 lg:grid-cols-[1fr_360px]">
    <div>
        @if ($webhooks->isEmpty())
            <div class="lf-card flex flex-col items-center justify-center px-6 py-16 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
                </span>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">No webhooks yet</h3>
                <p class="mt-1.5 max-w-sm text-sm text-slate-500">Get notified at your endpoint when links are created or flagged. Each request is signed with HMAC-SHA256.</p>
            </div>
        @else
            <div class="lf-card divide-y divide-slate-100">
                @foreach ($webhooks as $w)
                    <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                        <div class="min-w-0">
                            <p class="truncate font-mono text-sm text-slate-700">{{ $w->url }}</p>
                            <div class="mt-1 flex flex-wrap gap-1">
                                @foreach ((array) $w->events as $event)
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">{{ $event }}</span>
                                @endforeach
                            </div>
                        </div>
                        <form method="POST" action="{{ route('webhooks.destroy', $w) }}" data-confirm="Remove this webhook?" data-confirm-ok="Remove webhook">
                            @csrf @method('DELETE')
                            <button type="submit" class="rounded-md p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Remove">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="lf-card p-6">
        <h3 class="text-sm font-semibold text-slate-900">Add an endpoint</h3>
        <form method="POST" action="{{ route('webhooks.store') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="url" class="lf-label">Payload URL</label>
                <input id="url" name="url" type="url" value="{{ old('url') }}" class="lf-input" placeholder="https://your-app.com/webhooks/linkforge">
                @error('url') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <span class="lf-label">Events</span>
                <div class="space-y-2">
                    @foreach (['link.created' => 'Link created', 'link.clicked' => 'Link clicked', 'link.flagged' => 'Link flagged'] as $value => $label)
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="events[]" value="{{ $value }}" @checked(in_array($value, old('events', ['link.created'])))
                                   class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                            {{ $label }} <code class="text-xs text-slate-400">{{ $value }}</code>
                        </label>
                    @endforeach
                </div>
                @error('events') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="lf-btn">Add webhook</button>
        </form>
    </div>
</div>
