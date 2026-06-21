<?php

/*
|--------------------------------------------------------------------------
| Over-the-air (OTA) update channel
|--------------------------------------------------------------------------
|
| Buyers can check for and apply updates from inside the app, pulling from the
| author's relay. The TRUST ROOT is the bundled Ed25519 public key below: every
| release is signed offline by the author and verified here BEFORE anything is
| applied, so a compromised relay can never forge an update.
|
*/

return [

    // Ed25519 PUBLIC keys allowed to sign updates, keyed by key_id. The active key is a
    // hard-coded constant (the authoritative trust root). Env may only ADD rotation keys
    // (LF_UPDATE_EXTRA_KEYS="id:base64,id2:base64"), never replace the baked one — so an
    // edited .env cannot swap the trust root.
    'public_keys' => array_merge(
        ['lf-2026-06' => 'hyHJpEgDzT3sRH3+U2zkSjbh+r3v/2khIluQ6JVSUNA='],
        (static function (): array {
            $extra = [];
            foreach (array_filter(array_map('trim', explode(',', (string) env('LF_UPDATE_EXTRA_KEYS', '')))) as $pair) {
                [$id, $b64] = array_pad(explode(':', $pair, 2), 2, '');
                if ($id !== '' && $b64 !== '') {
                    $extra[$id] = $b64;
                }
            }

            return $extra;
        })(),
    ),

    // The update channel is the same relay that verifies licenses.
    'channel_url' => rtrim((string) env('LF_LICENSE_RELAY', 'https://license.sangeeth.biz'), '/'),

    // Never apply anything below this floor (defends against rollback even if other checks slip).
    'min_version' => env('LF_UPDATE_MIN_VERSION', '1.0.0'),

    // Hard ceiling on a downloaded package, in bytes (defends a hostile relay from filling the disk).
    'max_package_bytes' => (int) env('LF_UPDATE_MAX_BYTES', 80 * 1024 * 1024),

    // Quietly check for updates on a schedule and show a badge. NEVER auto-applies.
    'auto_check' => filter_var(env('LF_UPDATE_AUTO_CHECK', true), FILTER_VALIDATE_BOOL),
    'check_interval_hours' => (int) env('LF_UPDATE_CHECK_INTERVAL', 24),
];
