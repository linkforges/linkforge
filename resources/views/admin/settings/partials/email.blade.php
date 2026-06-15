<form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
    @csrf @method('PUT')
    <input type="hidden" name="section" value="email">

    <div class="lf-card p-6">
        <h3 class="mb-1 text-sm font-semibold text-slate-900">SMTP server</h3>
        <p class="mb-4 text-xs text-slate-400">Set a host to send mail over SMTP. Leave the host empty to use the server default (PHP mail / log).</p>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="lf-label" for="mail_host">Host</label>
                <input id="mail_host" name="mail_host" value="{{ old('mail_host', $s['mail_host'] ?? '') }}" class="lf-input" placeholder="smtp.mailgun.org">
            </div>
            <div>
                <label class="lf-label" for="mail_port">Port</label>
                <input id="mail_port" name="mail_port" type="number" value="{{ old('mail_port', $s['mail_port'] ?? '587') }}" class="lf-input">
            </div>
            <div>
                <label class="lf-label" for="mail_encryption">Encryption</label>
                <select id="mail_encryption" name="mail_encryption" class="lf-input">
                    @foreach ($encryptions as $val => $label)
                        <option value="{{ $val }}" @selected(old('mail_encryption', $s['mail_encryption'] ?? 'tls') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lf-label" for="mail_username">Username</label>
                <input id="mail_username" name="mail_username" value="{{ old('mail_username', $s['mail_username'] ?? '') }}" class="lf-input" autocomplete="off">
            </div>
            <div>
                @include('admin.settings.partials.secret-field', ['field' => 'mail_password', 'label' => 'Password'])
            </div>
        </div>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-900">From address</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="lf-label" for="mail_from_address">Email</label>
                <input id="mail_from_address" name="mail_from_address" type="email" value="{{ old('mail_from_address', $s['mail_from_address'] ?? '') }}" class="lf-input" placeholder="hello@yourdomain.com">
                @error('mail_from_address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="lf-label" for="mail_from_name">Name</label>
                <input id="mail_from_name" name="mail_from_name" value="{{ old('mail_from_name', $s['mail_from_name'] ?? config('linkforge.name')) }}" class="lf-input">
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Save SMTP settings</button>
    </div>
</form>

{{-- Email notifications: per-event templates + on/off --}}
<div class="lf-card mt-6 p-6">
    <h3 class="text-sm font-semibold text-slate-900">Email notifications</h3>
    <p class="mt-1 mb-4 text-xs text-slate-400">Edit the copy and toggle each automated email. Use <code class="rounded bg-slate-100 px-1 text-[11px]">@{{ token }}</code> placeholders; they are filled in when the email is sent.</p>

    <div class="space-y-2.5">
        @foreach ($emailEvents as $key => $ev)
            @php $tpl = $emailTemplates[$key]; @endphp
            <details class="group rounded-xl border border-slate-200">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3">
                    <span class="min-w-0">
                        <span class="flex items-center gap-2 text-sm font-medium text-slate-800">
                            {{ $ev['label'] }}
                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500">{{ $ev['audience'] }}</span>
                        </span>
                        <span class="mt-0.5 block truncate text-xs text-slate-400">{{ $ev['description'] }}</span>
                    </span>
                    <span class="flex shrink-0 items-center gap-2">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tpl['enabled'] ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-500' }}">{{ $tpl['enabled'] ? 'On' : 'Off' }}</span>
                        <svg class="h-4 w-4 text-slate-400 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </summary>
                <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-3 border-t border-slate-100 p-4">
                    @csrf @method('PUT')
                    <input type="hidden" name="section" value="email_template">
                    <input type="hidden" name="event" value="{{ $key }}">
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="enabled" value="1" @checked($tpl['enabled']) class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                        Send this email
                    </label>
                    <div>
                        <label class="lf-label">Subject</label>
                        <input name="subject" value="{{ $tpl['subject'] }}" class="lf-input" required>
                    </div>
                    <div>
                        <label class="lf-label">Body</label>
                        <textarea name="body" rows="6" class="lf-input font-mono text-xs leading-relaxed" required>{{ $tpl['body'] }}</textarea>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs text-slate-400">Placeholders:</span>
                        @foreach (\App\Support\EmailEvents::placeholders($key) as $ph)
                            @php $token = '{{ '.$ph.' }}'; @endphp
                            <code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-600">{{ $token }}</code>
                        @endforeach
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Save template</button>
                    </div>
                </form>
            </details>
        @endforeach
    </div>
</div>
