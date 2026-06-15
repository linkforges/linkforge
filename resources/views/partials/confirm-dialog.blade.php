{{--
  In-app confirmation modal. Replaces window.confirm() for any form with a
  [data-confirm] attribute (optional [data-confirm-ok] sets the button label).
  Fully self-contained (scoped CSS + JS) so it needs no asset build.
--}}
<div id="lf-confirm" aria-hidden="true">
    <div class="lf-modal" role="dialog" aria-modal="true" aria-labelledby="lf-confirm-title">
        <div class="lf-row">
            <span class="lf-ico" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01"/></svg>
            </span>
            <div>
                <h2 id="lf-confirm-title">Please confirm</h2>
                <p id="lf-confirm-message"></p>
            </div>
        </div>
        <div class="lf-acts">
            <button type="button" id="lf-confirm-cancel" class="lf-cancel">Cancel</button>
            <button type="button" id="lf-confirm-ok" class="lf-ok">Confirm</button>
        </div>
    </div>
</div>

<style>
    #lf-confirm { position: fixed; inset: 0; z-index: 100; display: none; align-items: center; justify-content: center; padding: 1rem; background: rgba(15, 23, 42, .5); -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px); }
    #lf-confirm.is-open { display: flex; }
    #lf-confirm .lf-modal { width: 100%; max-width: 24rem; background: #fff; border-radius: 1rem; box-shadow: 0 20px 50px rgba(2, 6, 23, .25); padding: 1.5rem; animation: lf-pop .12s ease-out; }
    #lf-confirm .lf-row { display: flex; align-items: flex-start; gap: .875rem; }
    #lf-confirm .lf-ico { display: flex; height: 2.5rem; width: 2.5rem; flex: none; align-items: center; justify-content: center; border-radius: 9999px; background: #fef2f2; color: #dc2626; }
    #lf-confirm h2 { margin: 0; font-size: 1rem; font-weight: 600; color: #0f172a; }
    #lf-confirm p { margin: .25rem 0 0; font-size: .875rem; line-height: 1.5; color: #64748b; }
    #lf-confirm .lf-acts { margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: .625rem; }
    #lf-confirm button { border: 0; border-radius: .5rem; padding: .5rem 1rem; font-size: .875rem; cursor: pointer; }
    #lf-confirm .lf-cancel { background: #fff; border: 1px solid #cbd5e1; color: #334155; font-weight: 500; }
    #lf-confirm .lf-cancel:hover { background: #f8fafc; }
    #lf-confirm .lf-ok { background: #dc2626; color: #fff; font-weight: 600; }
    #lf-confirm .lf-ok:hover { background: #b91c1c; }
    @keyframes lf-pop { from { opacity: 0; transform: translateY(6px) scale(.98); } to { opacity: 1; transform: none; } }
    .dark #lf-confirm .lf-modal { background: var(--lf-surface); }
    .dark #lf-confirm h2 { color: #f1f5f9; }
    .dark #lf-confirm p { color: #94a3b8; }
    .dark #lf-confirm .lf-ico { background: #3a1d1d; }
    .dark #lf-confirm .lf-cancel { background: #1e293b; border-color: #334155; color: #e2e8f0; }
    .dark #lf-confirm .lf-cancel:hover { background: #273449; }
</style>

<script>
    (function () {
        var root = document.getElementById('lf-confirm');
        if (!root) return;
        var message = document.getElementById('lf-confirm-message');
        var okBtn = document.getElementById('lf-confirm-ok');
        var cancelBtn = document.getElementById('lf-confirm-cancel');
        var pending = null;

        function open(form) {
            pending = form;
            message.textContent = form.getAttribute('data-confirm') || 'Are you sure?';
            okBtn.textContent = form.getAttribute('data-confirm-ok') || 'Confirm';
            root.classList.add('is-open');
            root.setAttribute('aria-hidden', 'false');
            okBtn.focus();
        }
        function close() {
            root.classList.remove('is-open');
            root.setAttribute('aria-hidden', 'true');
            pending = null;
        }

        // Capture phase: intercept before the form's default submit.
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (form && form.nodeName === 'FORM' && form.hasAttribute('data-confirm') && !form.dataset.lfConfirmed) {
                e.preventDefault();
                open(form);
            }
        }, true);

        okBtn.addEventListener('click', function () {
            if (!pending) return;
            var form = pending;
            form.dataset.lfConfirmed = '1'; // let the resubmit pass through
            close();
            if (typeof form.requestSubmit === 'function') { form.requestSubmit(); } else { form.submit(); }
        });
        cancelBtn.addEventListener('click', close);
        root.addEventListener('click', function (e) { if (e.target === root) close(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && root.classList.contains('is-open')) close(); });
    })();
</script>
