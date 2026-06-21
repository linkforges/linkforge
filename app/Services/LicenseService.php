<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

/**
 * Verifies a CodeCanyon purchase code against the author-hosted relay (which holds
 * the Envato personal token and calls the Envato API), then records the result.
 *
 * A "valid" verdict is only trusted when it carries an Ed25519 signature that this
 * build's baked public key (config linkforge.license.verify_public_key) verifies —
 * so a fake or local relay cannot forge a valid license.
 *
 * Verification ALWAYS fails open: a definitively-bad code is rejected, but a missing /
 * unreachable / unsigned relay response never blocks the install or the running site —
 * it is stored as "unverified" and re-checked later. This keeps the script compliant
 * with Envato's rule that licensing must never break the buyer's site.
 *
 * @phpstan-type VerifyResult array{valid:bool, message:string, unverified?:bool, license?:array<string,mixed>|null}
 */
class LicenseService
{
    private const CODE_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function relayUrl(): string
    {
        return rtrim((string) config('linkforge.license.relay_url', ''), '/');
    }

    /**
     * @return VerifyResult
     */
    public function verify(string $purchaseCode, ?string $domain = null): array
    {
        $code = trim($purchaseCode);

        if (! preg_match(self::CODE_PATTERN, $code)) {
            return ['valid' => false, 'message' => 'That does not look like a valid Envato purchase code (it should be a long code with dashes).'];
        }

        $relay = $this->relayUrl();
        if ($relay === '') {
            return ['valid' => true, 'unverified' => true, 'message' => 'Saved. Online verification is not configured, so the code was stored without checking.'];
        }

        $domainUsed = $domain ?: request()->getHost();

        try {
            $res = Http::timeout(10)->acceptJson()->asJson()->post($relay.'/verify', [
                'purchase_code' => $code,
                'domain' => $domainUsed,
                'item_id' => config('linkforge.license.item_id') ?: null,
            ]);

            if ($res->successful() && $res->json('valid') === true) {
                // Only trust a "valid" verdict that carries a signature our baked key verifies.
                if ($this->signatureValid((array) $res->json(), $code, $domainUsed)) {
                    return ['valid' => true, 'message' => 'License verified with Envato.', 'license' => $res->json('license')];
                }

                // Valid-looking but unsigned/forged-looking — fail open as unverified, never block.
                return ['valid' => true, 'unverified' => true, 'message' => 'Saved. The verification response could not be cryptographically confirmed; it will be re-checked automatically.'];
            }

            // A definitive "no" from the relay (bad / used / revoked code) is a hard fail.
            if ($res->status() === 422 || $res->json('valid') === false) {
                return ['valid' => false, 'message' => (string) ($res->json('message') ?: 'This purchase code could not be verified.')];
            }

            // Transient relay/Envato error — fail open.
            return ['valid' => true, 'unverified' => true, 'message' => 'Saved. The verification server could not be reached right now; this will be re-checked automatically.'];
        } catch (\Throwable $e) {
            return ['valid' => true, 'unverified' => true, 'message' => 'Saved. The verification server is unreachable right now; this will be re-checked automatically.'];
        }
    }

    /** Canonical string the relay signs and the app verifies. MUST match the relay byte-for-byte. */
    public function canonical(string $code, string $domain, string $issuedAt): string
    {
        return 'lf-license-v1|'.strtolower(trim($code)).'|'.$domain.'|valid|'.$issuedAt;
    }

    /** Verify the relay's Ed25519 signature over a "valid" verdict — bound to this request + fresh. */
    private function signatureValid(array $json, string $code, string $domain): bool
    {
        $pub = base64_decode((string) config('linkforge.license.verify_public_key'), true);
        $sig = base64_decode((string) ($json['signature'] ?? ''), true);
        $issuedAt = (string) ($json['issued_at'] ?? '');

        if (! is_string($pub) || strlen($pub) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            || ! is_string($sig) || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES || $issuedAt === '') {
            return false;
        }
        // Reject stale/replayed responses (15-minute clock-skew window).
        $ts = strtotime($issuedAt);
        if ($ts === false || abs(time() - $ts) > 900) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($sig, $this->canonical($code, $domain, $issuedAt), $pub);
    }

    /**
     * Re-verify the stored license (scheduled weekly + manual button). FAIL-OPEN: a
     * transient/unreachable/unsigned response NEVER downgrades a known-good license or
     * blocks the site — only a definitive "no" flips the status to "invalid".
     *
     * @return VerifyResult
     */
    public function recheck(): array
    {
        $code = (string) Setting::get('license_code', '');
        if ($code === '') {
            return ['valid' => false, 'message' => 'No license code on file.'];
        }

        // Check against the real install domain (CLI has no request host).
        $domain = parse_url((string) config('app.url'), PHP_URL_HOST) ?: request()->getHost();
        $result = $this->verify($code, $domain);
        $now = now()->toIso8601String();

        if (($result['valid'] ?? false) && empty($result['unverified'])) {
            Setting::putMany(['license_status' => 'active', 'license_verified_at' => $now, 'license_ok_at' => $now, 'license_checked_at' => $now]);
        } elseif (! ($result['valid'] ?? false)) {
            // Definitive revoke / refund / wrong item — surface it, never block.
            Setting::putMany(['license_status' => 'invalid', 'license_checked_at' => $now]);
        } else {
            // Transient / unreachable / unsigned — keep the last known status, just record the attempt.
            Setting::put('license_checked_at', $now);
        }

        return $result;
    }

    /**
     * Persist the outcome of a verify() call.
     *
     * @param  VerifyResult  $result
     */
    public function store(string $purchaseCode, array $result): void
    {
        $confirmed = ($result['valid'] ?? false) && empty($result['unverified']);
        $now = now()->toIso8601String();

        $pairs = [
            'license_code' => trim($purchaseCode),
            'license_status' => $confirmed ? 'active' : 'unverified',
            'license_verified_at' => $now,
            'license_checked_at' => $now,
        ];
        if ($confirmed) {
            $pairs['license_ok_at'] = $now; // last-known-good marker (distinguishes "was valid" from "never verified")
        }

        Setting::putMany($pairs);
    }

    /**
     * Current license state for display in admin.
     *
     * @return array{code:string, status:string, verified_at:?string, ok_at:?string, checked_at:?string}
     */
    public function status(): array
    {
        return [
            'code' => (string) Setting::get('license_code', ''),
            'status' => (string) Setting::get('license_status', 'none'),
            'verified_at' => Setting::get('license_verified_at'),
            'ok_at' => Setting::get('license_ok_at'),
            'checked_at' => Setting::get('license_checked_at'),
        ];
    }

    /** Should the admin-only "license problem" banner show? Only on a definitive revoke. */
    public function hasProblem(): bool
    {
        return Setting::get('license_status') === 'invalid';
    }
}
