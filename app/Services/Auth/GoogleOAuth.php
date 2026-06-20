<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * "Sign in with Google" via OAuth 2.0 + OpenID Connect.
 * Docs: https://developers.google.com/identity/protocols/oauth2/web-server
 */
class GoogleOAuth extends AbstractOAuthProvider
{
    protected function key(): string
    {
        return 'google';
    }

    protected function authEndpoint(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    protected function tokenEndpoint(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    protected function scope(): string
    {
        return 'openid email profile';
    }

    protected function authParams(): array
    {
        return [
            'access_type' => 'online',
            'prompt' => 'select_account',
            'include_granted_scopes' => 'true',
        ];
    }

    public function fetchProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)->acceptJson()->get('https://openidconnect.googleapis.com/v1/userinfo');

        if ($response->failed() || ! $response->json('sub')) {
            throw new RuntimeException('Could not read the Google profile.');
        }

        return [
            'id' => (string) $response->json('sub'),
            'email' => (string) $response->json('email'),
            'email_verified' => (bool) $response->json('email_verified'),
            'name' => $response->json('name'),
            'avatar' => $response->json('picture'),
        ];
    }
}
