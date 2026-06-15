<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

/**
 * Verifies a CodeCanyon purchase code against the author-hosted relay (which
 * holds the Envato personal token and calls the Envato API), then records the
 * result locally.
 *
 * Verification ALWAYS fails open: an invalid code is rejected, but a missing /
 * unreachable relay never blocks the install or the running site — it is stored
 * as "unverified" and can be re-checked later. This keeps the script compliant
 * with Envato's rule that licensing must not break the buyer's site.
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
            // No verification server configured — accept but flag as unverified.
            return ['valid' => true, 'unverified' => true, 'message' => 'Saved. Online verification is not configured, so the code was stored without checking.'];
        }

        try {
            $res = Http::timeout(10)->acceptJson()->asJson()->post($relay.'/verify', [
                'purchase_code' => $code,
                'domain' => $domain ?: request()->getHost(),
                'item_id' => config('linkforge.license.item_id') ?: null,
            ]);

            if ($res->successful() && $res->json('valid') === true) {
                return ['valid' => true, 'message' => 'License verified with Envato.', 'license' => $res->json('license')];
            }

            // A definitive "no" from the relay (bad/used code) is a hard fail.
            if ($res->status() === 422 || $res->json('valid') === false) {
                return ['valid' => false, 'message' => (string) ($res->json('message') ?: 'This purchase code could not be verified.')];
            }

            // Transient relay/Envato error — fail open.
            return ['valid' => true, 'unverified' => true, 'message' => 'Saved. The verification server could not be reached right now; this will be re-checked automatically.'];
        } catch (\Throwable $e) {
            return ['valid' => true, 'unverified' => true, 'message' => 'Saved. The verification server is unreachable right now; this will be re-checked automatically.'];
        }
    }

    /**
     * Persist the outcome of a verify() call.
     *
     * @param  VerifyResult  $result
     */
    public function store(string $purchaseCode, array $result): void
    {
        Setting::putMany([
            'license_code' => trim($purchaseCode),
            'license_status' => ($result['unverified'] ?? false) ? 'unverified' : 'active',
            'license_verified_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Current license state for display in admin.
     *
     * @return array{code:string, status:string, verified_at:?string}
     */
    public function status(): array
    {
        return [
            'code' => (string) Setting::get('license_code', ''),
            'status' => (string) Setting::get('license_status', 'none'),
            'verified_at' => Setting::get('license_verified_at'),
        ];
    }
}
