{{--
  Password-confirmation modal + window.lfSecureFetch().

  Fortify and laravel/passkeys gate sensitive endpoints (2FA, passkey
  management) behind the `password.confirm` middleware, which answers JSON
  requests with HTTP 423. lfSecureFetch(method, url, body) issues the request
  and, on a 423, pops this modal to confirm the password, then retries. It
  returns the final Response, or null if the user cancels.
--}}
<div id="pwd-confirm" data-url="{{ url('user/confirm-password') }}" aria-hidden="true">
    <div class="pc-modal" role="dialog" aria-modal="true">
        <h2>Confirm your password</h2>
        <p>For your security, please confirm your password to continue.</p>
        <input id="pwd-confirm-input" type="password" autocomplete="current-password" placeholder="Password">
        <p id="pwd-confirm-error"></p>
        <div class="pc-acts">
            <button type="button" id="pwd-confirm-cancel" class="pc-cancel">Cancel</button>
            <button type="button" id="pwd-confirm-ok" class="pc-ok">Confirm</button>
        </div>
    </div>
</div>

<style>
    #pwd-confirm { position: fixed; inset: 0; z-index: 110; display: none; align-items: center; justify-content: center; padding: 1rem; background: rgba(15,23,42,.5); -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px); }
    #pwd-confirm.is-open { display: flex; }
    #pwd-confirm .pc-modal { width: 100%; max-width: 24rem; background: #fff; border-radius: 1rem; box-shadow: 0 20px 50px rgba(2,6,23,.25); padding: 1.5rem; }
    #pwd-confirm h2 { margin: 0; font-size: 1rem; font-weight: 600; color: #0f172a; }
    #pwd-confirm p { margin: .25rem 0 0; font-size: .8125rem; color: #64748b; }
    #pwd-confirm input { margin-top: 1rem; width: 100%; border: 1px solid #cbd5e1; border-radius: .5rem; padding: .5rem .75rem; font-size: .875rem; }
    #pwd-confirm input:focus { outline: none; border-color: var(--color-brand-500); box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-brand-500) 25%, transparent); }
    #pwd-confirm #pwd-confirm-error { color: #dc2626; font-size: .75rem; min-height: 1rem; }
    #pwd-confirm .pc-acts { margin-top: 1rem; display: flex; justify-content: flex-end; gap: .625rem; }
    #pwd-confirm button { border: 0; border-radius: .5rem; padding: .5rem 1rem; font-size: .875rem; cursor: pointer; }
    #pwd-confirm .pc-cancel { background: #fff; border: 1px solid #cbd5e1; color: #334155; font-weight: 500; }
    #pwd-confirm .pc-ok { background: var(--color-brand-600); color: #fff; font-weight: 600; }
    .dark #pwd-confirm .pc-modal { background: var(--lf-surface); }
    .dark #pwd-confirm h2 { color: #f1f5f9; }
    .dark #pwd-confirm p { color: #94a3b8; }
    .dark #pwd-confirm input { background: #0b1220; border-color: #334155; color: #e2e8f0; }
    .dark #pwd-confirm .pc-cancel { background: #1e293b; border-color: #334155; color: #e2e8f0; }
</style>

<script>
(function () {
    var TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var pc = document.getElementById('pwd-confirm');
    var input = document.getElementById('pwd-confirm-input');
    var errEl = document.getElementById('pwd-confirm-error');
    var resolver = null;

    function open() {
        return new Promise(function (resolve) {
            resolver = resolve;
            input.value = ''; errEl.textContent = '';
            pc.classList.add('is-open'); input.focus();
        });
    }
    function settle(ok) { pc.classList.remove('is-open'); var r = resolver; resolver = null; if (r) r(ok); }

    document.getElementById('pwd-confirm-cancel').addEventListener('click', function () { settle(false); });
    document.getElementById('pwd-confirm-ok').addEventListener('click', submit);
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); submit(); } });
    pc.addEventListener('click', function (e) { if (e.target === pc) settle(false); });

    async function submit() {
        var res = await fetch(pc.dataset.url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': TOKEN, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ password: input.value })
        });
        if (res.ok) { settle(true); }
        else { errEl.textContent = 'That password is incorrect.'; input.select(); }
    }

    // Promise<boolean> — resolves true once the password is confirmed, false if cancelled.
    window.__lfAskPassword = open;

    window.lfSecureFetch = async function (method, url, body) {
        function opts() {
            var o = { method: method, headers: { 'X-CSRF-TOKEN': TOKEN, 'Accept': 'application/json' } };
            if (body !== undefined && body !== null) { o.headers['Content-Type'] = 'application/json'; o.body = JSON.stringify(body); }
            return o;
        }
        var res = await fetch(url, opts());
        if (res.status === 423) {
            var ok = await open();
            if (!ok) return null;
            res = await fetch(url, opts());
        }
        return res;
    };
})();
</script>
