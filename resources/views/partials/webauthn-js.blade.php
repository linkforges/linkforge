{{--
  window.LFPasskey — runs the browser side of the WebAuthn ceremonies and
  converts between the base64url JSON that laravel/passkeys speaks and the
  ArrayBuffers that navigator.credentials expects. No build step / no library.
--}}
<script>
(function () {
    function b64urlToBuf(s) {
        s = String(s).replace(/-/g, '+').replace(/_/g, '/');
        var pad = s.length % 4;
        if (pad) { s += '===='.slice(pad); }
        var bin = atob(s);
        var bytes = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) { bytes[i] = bin.charCodeAt(i); }
        return bytes.buffer;
    }
    function bufToB64url(buf) {
        var bytes = new Uint8Array(buf);
        var bin = '';
        for (var i = 0; i < bytes.length; i++) { bin += String.fromCharCode(bytes[i]); }
        return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    window.LFPasskey = {
        supported: function () {
            return !!(window.PublicKeyCredential && navigator.credentials && navigator.credentials.create);
        },

        // Run a registration ceremony from server options; returns a JSON-ready credential.
        create: async function (options) {
            var pk = Object.assign({}, options);
            pk.challenge = b64urlToBuf(options.challenge);
            pk.user = Object.assign({}, options.user, { id: b64urlToBuf(options.user.id) });
            if (Array.isArray(options.excludeCredentials)) {
                pk.excludeCredentials = options.excludeCredentials.map(function (c) {
                    return Object.assign({}, c, { id: b64urlToBuf(c.id) });
                });
            }
            var cred = await navigator.credentials.create({ publicKey: pk });
            var r = cred.response;
            var out = {
                id: cred.id,
                rawId: bufToB64url(cred.rawId),
                type: cred.type,
                response: {
                    clientDataJSON: bufToB64url(r.clientDataJSON),
                    attestationObject: bufToB64url(r.attestationObject)
                },
                clientExtensionResults: cred.getClientExtensionResults ? cred.getClientExtensionResults() : {}
            };
            if (r.getTransports) { try { out.response.transports = r.getTransports(); } catch (e) {} }
            return out;
        },

        // Run an authentication ceremony from server options; returns a JSON-ready credential.
        get: async function (options) {
            var pk = Object.assign({}, options);
            pk.challenge = b64urlToBuf(options.challenge);
            if (Array.isArray(options.allowCredentials)) {
                pk.allowCredentials = options.allowCredentials.map(function (c) {
                    return Object.assign({}, c, { id: b64urlToBuf(c.id) });
                });
            }
            var cred = await navigator.credentials.get({ publicKey: pk });
            var r = cred.response;
            return {
                id: cred.id,
                rawId: bufToB64url(cred.rawId),
                type: cred.type,
                response: {
                    clientDataJSON: bufToB64url(r.clientDataJSON),
                    authenticatorData: bufToB64url(r.authenticatorData),
                    signature: bufToB64url(r.signature),
                    userHandle: r.userHandle ? bufToB64url(r.userHandle) : null
                },
                clientExtensionResults: cred.getClientExtensionResults ? cred.getClientExtensionResults() : {}
            };
        }
    };
})();
</script>
