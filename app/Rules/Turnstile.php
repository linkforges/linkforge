<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

/**
 * Cloudflare Turnstile CAPTCHA verification. No-op when no secret is configured,
 * so local/dev and unconfigured installs keep working. Fails open on network
 * errors to avoid locking users out if Cloudflare is unreachable.
 */
class Turnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('linkforge.safety.turnstile.secret');
        if (! $secret) {
            return;
        }

        try {
            $response = Http::asForm()->timeout(5)->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                ['secret' => $secret, 'response' => (string) $value],
            )->json();

            if (! ($response['success'] ?? false)) {
                $fail('Captcha verification failed. Please try again.');
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
