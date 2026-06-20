<form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
    @csrf @method('PUT')
    <input type="hidden" name="section" value="login">

    @php
        $providers = [
            'google' => [
                'idPlaceholder' => '1234567890-abc.apps.googleusercontent.com',
                'secretPlaceholder' => 'GOCSPX-...',
                'console' => 'https://console.cloud.google.com/apis/credentials',
                'consoleLabel' => 'Google Cloud Console → Credentials',
                'hint' => 'Create an OAuth client ID (type: Web application) and add the redirect URI below to "Authorized redirect URIs".',
            ],
            'github' => [
                'idPlaceholder' => 'Iv1.0123456789abcdef',
                'secretPlaceholder' => 'github client secret',
                'console' => 'https://github.com/settings/developers',
                'consoleLabel' => 'GitHub → Settings → Developer settings → OAuth Apps',
                'hint' => 'Register a new OAuth App and set its "Authorization callback URL" to the redirect URI below.',
            ],
            'facebook' => [
                'idPlaceholder' => 'App ID (numeric)',
                'secretPlaceholder' => 'App secret',
                'console' => 'https://developers.facebook.com/apps',
                'consoleLabel' => 'Meta for Developers → My Apps',
                'hint' => 'Add the "Facebook Login" product, then add the redirect URI below to "Valid OAuth Redirect URIs". Request the email permission.',
            ],
        ];
    @endphp

    @foreach (\App\Services\Auth\SocialProviders::MAP as $key => $meta)
        @php $p = $providers[$key]; @endphp
        <div class="lf-card p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Sign in with {{ $meta['label'] }}</h3>
                    <p class="mt-1 text-xs text-slate-400">Let users register and sign in with their {{ $meta['label'] }} account. When off, the button disappears and the OAuth routes 404.</p>
                </div>
                <label class="inline-flex shrink-0 cursor-pointer items-center gap-2 text-sm font-medium text-slate-600">
                    <input type="checkbox" name="{{ $key }}_login_enabled" value="1" @checked(($s[$key.'_login_enabled'] ?? '0') === '1')
                           class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30">
                    Enable
                </label>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="lf-label" for="{{ $key }}_client_id">Client ID</label>
                    <input id="{{ $key }}_client_id" name="{{ $key }}_client_id" value="{{ old($key.'_client_id', $s[$key.'_client_id'] ?? '') }}" class="lf-input" placeholder="{{ $p['idPlaceholder'] }}">
                </div>
                <div>
                    @include('admin.settings.partials.secret-field', ['field' => $key.'_client_secret', 'label' => 'Client secret', 'placeholder' => $p['secretPlaceholder']])
                </div>
            </div>

            <div class="mt-4">
                <p class="lf-label">Authorized redirect URI</p>
                <code class="block rounded-md bg-slate-50 px-3 py-2 font-mono text-xs break-all text-slate-600">{{ route('auth.'.$key.'.callback') }}</code>
            </div>

            <p class="mt-3 text-xs text-slate-400">{{ $p['hint'] }} Get credentials at
                <a href="{{ $p['console'] }}" target="_blank" rel="noopener" class="font-medium text-brand-600 hover:text-brand-700">{{ $p['consoleLabel'] }}</a>.
            </p>
        </div>
    @endforeach

    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Save social login</button>
    </div>
</form>
