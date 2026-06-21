@if (session('plain_token'))
    <div class="mb-6 rounded-xl border border-brand-200 bg-brand-50 p-4">
        <p class="text-sm font-medium text-brand-800">Copy your new token now. It will not be shown again.</p>
        <div class="mt-2 flex items-center gap-2">
            <code class="flex-1 truncate rounded-lg border border-brand-200 bg-white px-3 py-2 font-mono text-xs text-slate-700">{{ session('plain_token') }}</code>
            <button type="button" data-copy="{{ session('plain_token') }}" class="rounded-lg border border-brand-300 bg-white px-3 py-2 text-xs font-medium text-brand-700 hover:bg-brand-100">Copy</button>
        </div>
    </div>
@endif

@if (! $allowed)
    <div class="lf-card flex flex-col items-center justify-center px-6 py-16 text-center">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/></svg>
        </span>
        <h3 class="mt-4 text-lg font-semibold text-slate-900">Build on the {{ config('linkforge.name') }} API</h3>
        <p class="mt-1.5 max-w-sm text-sm text-slate-500">Programmatic link creation and analytics are available on the Starter plan and above.</p>
        <a href="{{ route('billing.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">View plans</a>
    </div>
@else
    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div>
            @if ($tokens->isEmpty())
                <div class="lf-card px-5 py-10 text-center text-sm text-slate-500">No tokens yet. Create one to start using the API.</div>
            @else
                <div class="lf-card divide-y divide-slate-100">
                    @foreach ($tokens as $t)
                        <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-slate-900">{{ $t->name }}</p>
                                <p class="text-xs text-slate-400">Last used {{ $t->last_used_at?->diffForHumans() ?? 'never' }} · created {{ $t->created_at?->diffForHumans() }}</p>
                            </div>
                            <form method="POST" action="{{ route('tokens.destroy', $t->id) }}" data-confirm="Revoke this token?" data-confirm-ok="Revoke token">
                                @csrf @method('DELETE')
                                <button type="submit" class="rounded-md p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Revoke">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            @php $apiBase = rtrim(config('app.url'), '/').'/api/v1'; @endphp
            <div class="lf-card mt-6 p-5">
                <h3 class="text-sm font-semibold text-slate-900">API reference</h3>
                <p class="mt-1 text-sm text-slate-500">A small REST API for creating and managing your links. All responses are JSON.</p>

                <h4 class="mt-5 text-xs font-semibold tracking-wide text-slate-400 uppercase">Authentication</h4>
                <p class="mt-1.5 text-sm text-slate-600">Send your token as a <strong>Bearer</strong> header on every request. Keep tokens secret — anyone with one can act as your account. Requests are rate limited to <strong>120 per minute</strong> per token.</p>
                <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs leading-relaxed text-white"><code>Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

Base URL:  {{ $apiBase }}</code></pre>

                <h4 class="mt-5 text-xs font-semibold tracking-wide text-slate-400 uppercase">Endpoints</h4>
                <div class="mt-2 overflow-x-auto rounded-lg border border-slate-200">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs tracking-wide text-slate-400 uppercase">
                            <tr><th class="px-4 py-2 font-medium">Method</th><th class="px-4 py-2 font-medium">Endpoint</th><th class="px-4 py-2 font-medium">Description</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            <tr><td class="px-4 py-2 font-mono text-xs text-emerald-700">GET</td><td class="px-4 py-2 font-mono text-xs">/me</td><td class="px-4 py-2">The authenticated account</td></tr>
                            <tr><td class="px-4 py-2 font-mono text-xs text-emerald-700">GET</td><td class="px-4 py-2 font-mono text-xs">/links</td><td class="px-4 py-2">List your links (paginated)</td></tr>
                            <tr><td class="px-4 py-2 font-mono text-xs text-sky-700">POST</td><td class="px-4 py-2 font-mono text-xs">/links</td><td class="px-4 py-2">Create a link</td></tr>
                            <tr><td class="px-4 py-2 font-mono text-xs text-emerald-700">GET</td><td class="px-4 py-2 font-mono text-xs">/links/{id}</td><td class="px-4 py-2">Fetch one link</td></tr>
                            <tr><td class="px-4 py-2 font-mono text-xs text-amber-700">PATCH</td><td class="px-4 py-2 font-mono text-xs">/links/{id}</td><td class="px-4 py-2">Update a link</td></tr>
                            <tr><td class="px-4 py-2 font-mono text-xs text-red-700">DELETE</td><td class="px-4 py-2 font-mono text-xs">/links/{id}</td><td class="px-4 py-2">Delete a link</td></tr>
                        </tbody>
                    </table>
                </div>

                <h4 class="mt-5 text-xs font-semibold tracking-wide text-slate-400 uppercase">Create a link</h4>
                <p class="mt-1.5 text-sm text-slate-600">Body fields: <code class="text-xs">long_url</code> (required), <code class="text-xs">alias</code> (optional, leave out for a random one), <code class="text-xs">title</code> (optional).</p>
                <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs leading-relaxed text-white"><code>curl -X POST {{ $apiBase }}/links \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"long_url":"https://example.com/page","alias":"summer","title":"Summer sale"}'</code></pre>

                <h4 class="mt-5 text-xs font-semibold tracking-wide text-slate-400 uppercase">List, update &amp; delete</h4>
                <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs leading-relaxed text-white"><code># List (paginate with ?per_page=1..100 &amp; ?page=)
curl {{ $apiBase }}/links?per_page=50 -H "Authorization: Bearer YOUR_TOKEN"

# Update the destination, title, or active state
curl -X PATCH {{ $apiBase }}/links/123 \
  -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" \
  -d '{"is_active":false}'

# Delete
curl -X DELETE {{ $apiBase }}/links/123 -H "Authorization: Bearer YOUR_TOKEN"</code></pre>

                <h4 class="mt-5 text-xs font-semibold tracking-wide text-slate-400 uppercase">The link object</h4>
                <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs leading-relaxed text-white"><code>{
  "id": 123,
  "alias": "summer",
  "short_url": "https://yourdomain.com/summer",
  "destination": "https://example.com/page",
  "title": "Summer sale",
  "type": "direct",
  "clicks": 0,
  "is_active": true,
  "safety_status": "safe",
  "created_at": "2026-01-01T12:00:00+00:00"
}</code></pre>

                <h4 class="mt-5 text-xs font-semibold tracking-wide text-slate-400 uppercase">Response codes</h4>
                <ul class="mt-1.5 space-y-1 text-sm text-slate-600">
                    <li><code class="text-xs">200 / 201</code> — success.</li>
                    <li><code class="text-xs">401</code> — missing or invalid token.</li>
                    <li><code class="text-xs">403</code> — the link is not yours, or your plan has no API access.</li>
                    <li><code class="text-xs">422</code> — validation failed (a <code class="text-xs">message</code> and <code class="text-xs">errors</code> object are returned).</li>
                    <li><code class="text-xs">429</code> — rate limit exceeded; slow down and retry.</li>
                </ul>
            </div>
        </div>

        <div class="lf-card p-6">
            <h3 class="text-sm font-semibold text-slate-900">Create a token</h3>
            <form method="POST" action="{{ route('tokens.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="name" class="lf-label">Token name</label>
                    <input id="name" name="name" value="{{ old('name') }}" class="lf-input" placeholder="e.g. Zapier integration">
                    @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="lf-btn">Create token</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-copy]');
            if (!btn) return;
            navigator.clipboard.writeText(btn.getAttribute('data-copy'));
            btn.textContent = 'Copied';
            setTimeout(() => { btn.textContent = 'Copy'; }, 1200);
        });
    </script>
@endif
