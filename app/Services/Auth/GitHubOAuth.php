<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * "Sign in with GitHub" via OAuth 2.0.
 * Docs: https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps
 *
 * GitHub's API requires a User-Agent header, and a user's email may be private,
 * so we fall back to the /user/emails endpoint to find the primary verified one.
 */
class GitHubOAuth extends AbstractOAuthProvider
{
    protected function key(): string
    {
        return 'github';
    }

    protected function authEndpoint(): string
    {
        return 'https://github.com/login/oauth/authorize';
    }

    protected function tokenEndpoint(): string
    {
        return 'https://github.com/login/oauth/access_token';
    }

    protected function scope(): string
    {
        return 'read:user user:email';
    }

    public function fetchProfile(string $accessToken): array
    {
        $api = Http::withToken($accessToken)->withHeaders([
            'User-Agent' => (string) config('linkforge.name', 'LinkForge'),
            'Accept' => 'application/vnd.github+json',
        ]);

        $response = $api->get('https://api.github.com/user');
        if ($response->failed() || ! $response->json('id')) {
            throw new RuntimeException('Could not read the GitHub profile.');
        }

        $email = (string) $response->json('email');
        $verified = $email !== ''; // a public profile email is already verified

        // Private email: pick the primary verified address.
        if ($email === '') {
            $emails = $api->get('https://api.github.com/user/emails')->json();
            if (is_array($emails)) {
                $primary = collect($emails)->firstWhere('primary', true)
                    ?? collect($emails)->firstWhere('verified', true);
                $email = (string) ($primary['email'] ?? '');
                $verified = (bool) ($primary['verified'] ?? false);
            }
        }

        return [
            'id' => (string) $response->json('id'),
            'email' => $email,
            'email_verified' => $verified,
            'name' => $response->json('name') ?: $response->json('login'),
            'avatar' => $response->json('avatar_url'),
        ];
    }
}
