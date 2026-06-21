@if (session('webhook_secret'))
    <div class="mb-6 rounded-xl border border-brand-200 bg-brand-50 p-4">
        <p class="text-sm font-medium text-brand-800">Copy this webhook signing secret now. It will not be shown again.</p>
        <div class="mt-2 flex items-center gap-2">
            <code class="flex-1 truncate rounded-lg border border-brand-200 bg-white px-3 py-2 font-mono text-xs text-slate-700">{{ session('webhook_secret') }}</code>
            <button type="button" data-copy="{{ session('webhook_secret') }}" class="rounded-lg border border-brand-300 bg-white px-3 py-2 text-xs font-medium text-brand-700 hover:bg-brand-100">Copy</button>
        </div>
    </div>
@endif

<div class="grid gap-6 lg:grid-cols-[1fr_360px]">
    <div>
        @if ($webhooks->isEmpty())
            <div class="lf-card flex flex-col items-center justify-center px-6 py-16 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
                </span>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">No webhooks yet</h3>
                <p class="mt-1.5 max-w-sm text-sm text-slate-500">Get notified at your endpoint when links are created, clicked or flagged. Each request is signed with HMAC-SHA256.</p>
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

        {{-- Webhook guide --}}
        <div class="lf-card mt-6 p-5">
            <h3 class="text-sm font-semibold text-slate-900">How webhooks work</h3>
            <p class="mt-1 text-sm text-slate-500">When a subscribed event happens, we send a <strong>POST</strong> request with a JSON body to your endpoint.</p>

            <h4 class="mt-5 text-xs font-semibold tracking-wide text-slate-400 uppercase">Events</h4>
            <ul class="mt-1.5 space-y-1 text-sm text-slate-600">
                <li><code class="text-xs">link.created</code> — a new short link is created.</li>
                <li><code class="text-xs">link.clicked</code> — a real (non-bot) click is recorded.</li>
                <li><code class="text-xs">link.flagged</code> — a link is flagged as unsafe or reported.</li>
            </ul>

            <h4 class="mt-5 text-xs font-semibold tracking-wide text-slate-400 uppercase">Request format</h4>
            <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs leading-relaxed text-white"><code>POST https://your-app.com/webhooks
X-LinkForge-Event: link.clicked
X-LinkForge-Signature: &lt;hex hmac-sha256 of the raw body&gt;
Content-Type: application/json

{
  "event": "link.clicked",
  "data": {
    "id": 123, "alias": "summer",
    "short_url": "https://yourdomain.com/summer",
    "target": "https://example.com/page",
    "country": "US", "device": "mobile", "referer": "google.com"
  },
  "sent_at": "2026-01-01T12:00:00+00:00"
}</code></pre>

            <h4 class="mt-5 text-xs font-semibold tracking-wide text-slate-400 uppercase">Verify the signature</h4>
            <p class="mt-1.5 text-sm text-slate-600">Every request is signed with HMAC-SHA256 of the <strong>raw</strong> body, keyed with your endpoint's secret (shown once when you add it). Always verify before trusting the payload.</p>
            <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs leading-relaxed text-white"><code>// PHP
$payload   = file_get_contents('php://input');
$expected  = hash_hmac('sha256', $payload, $YOUR_WEBHOOK_SECRET);
$signature = $_SERVER['HTTP_X_LINKFORGE_SIGNATURE'] ?? '';

if (! hash_equals($expected, $signature)) {
    http_response_code(401);
    exit;
}</code></pre>

            <h4 class="mt-5 text-xs font-semibold tracking-wide text-slate-400 uppercase">Good to know</h4>
            <ul class="mt-1.5 space-y-1 text-sm text-slate-600">
                <li>Delivery runs through the scheduler, so the <strong>cron job</strong> must be installed.</li>
                <li>Endpoints must be public <code class="text-xs">https</code> URLs; internal/loopback addresses are rejected.</li>
                <li>Respond with a <code class="text-xs">2xx</code> status quickly to acknowledge receipt.</li>
            </ul>
        </div>
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

<script>
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-copy]');
        if (!btn) return;
        navigator.clipboard.writeText(btn.getAttribute('data-copy'));
        btn.textContent = 'Copied';
        setTimeout(() => { btn.textContent = 'Copy'; }, 1200);
    });
</script>
