<form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
    @csrf @method('PUT')
    <input type="hidden" name="section" value="login">

    <div class="lf-card p-6">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-lg border border-slate-200 bg-white">
                <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.76h3.57c2.08-1.92 3.27-4.74 3.27-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.76c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.84 14.09a6.6 6.6 0 0 1 0-4.18V7.07H2.18a11 11 0 0 0 0 9.86l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84C6.71 7.31 9.14 5.38 12 5.38z"/></svg>
            </span>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-slate-900">Sign in with Google</h3>
                <p class="mt-1 text-xs text-slate-400">Let users register and sign in with their Google account. When off, the buttons disappear and the OAuth routes are disabled.</p>
            </div>
        </div>

        <label class="mt-5 flex items-start gap-2.5 text-sm text-slate-600">
            <input type="checkbox" name="google_login_enabled" value="1" @checked(($s['google_login_enabled'] ?? '0') === '1')
                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
            <span>Enable "Sign in with Google"<br><span class="text-xs text-slate-400">Requires the credentials below.</span></span>
        </label>
    </div>

    <div class="lf-card p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-900">Google OAuth credentials</h3>
        <div class="space-y-4">
            <div>
                <label class="lf-label" for="google_client_id">Client ID</label>
                <input id="google_client_id" name="google_client_id" value="{{ old('google_client_id', $s['google_client_id'] ?? '') }}" class="lf-input" placeholder="1234567890-abc.apps.googleusercontent.com">
            </div>
            @include('admin.settings.partials.secret-field', ['field' => 'google_client_secret', 'label' => 'Client secret', 'placeholder' => 'GOCSPX-...'])
            <div>
                <p class="lf-label">Authorized redirect URI</p>
                <code class="block rounded-md bg-slate-50 px-3 py-2 font-mono text-xs break-all text-slate-600">{{ route('auth.google.callback') }}</code>
                <p class="mt-1 text-xs text-slate-400">Paste this exact URL into your OAuth client's "Authorized redirect URIs".</p>
            </div>
        </div>
    </div>

    <div class="lf-card border border-slate-200 bg-slate-50 p-5">
        <h4 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">How to get credentials</h4>
        <ol class="mt-2 list-decimal space-y-1 pl-5 text-xs text-slate-500">
            <li>Open the <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener" class="font-medium text-brand-600 hover:text-brand-700">Google Cloud Console &rarr; Credentials</a> and create an OAuth client ID (type: Web application).</li>
            <li>Add the redirect URI above to "Authorized redirect URIs", and your site URL to "Authorized JavaScript origins".</li>
            <li>Configure the OAuth consent screen (app name, support email, the <code>email</code> and <code>profile</code> scopes).</li>
            <li>Copy the generated Client ID and Client secret into the fields above and enable the toggle.</li>
        </ol>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Save social login</button>
    </div>
</form>
