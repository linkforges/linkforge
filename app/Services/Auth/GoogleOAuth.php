<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Google "Sign in with Google" using the OAuth 2.0 authorization-code flow,
 * spoken directly over HTTP (no SDK), consistent with the rest of the app.
 *
 * Flow (https://developers.google.com/identity/protocols/oauth2/web-server):
 *   1. Send the user to authUrl() with a CSRF `state`.
 *   2. Google redirects back to the callback with `code` + `state`.
 *   3. exchangeCode() trades the code for tokens at the token endpoint.
 *   4. fetchProfile() reads the OpenID Connect userinfo endpoint.
 */
class GoogleOAuth
{
    private const AUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    private const USERINFO_ENDPOINT = 'https://openidconnect.googleapis.com/v1/userinfo';

    public function __construct(private readonly string $redirectUri) {}

    public function enabled(): bool
    {
        return (bool) config('services.google.enabled') && $this->configured();
    }

    public function configured(): bool
    {
        return ! empty(config('services.google.client_id')) && ! empty(config('services.google.client_secret'));
    }

    /** Build the Google consent-screen URL the user is redirected to. */
    public function authUrl(string $state): string
    {
        return self::AUTH_ENDPOINT.'?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
            'include_granted_scopes' => 'true',
        ]);
    }

    /**
     * Exchange an authorization code for an access token.
     *
     * @return array{access_token:string,...}
     */
    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()
            ->acceptJson()
            ->post(self::TOKEN_ENDPOINT, [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => $this->redirectUri,
                'grant_type' => 'authorization_code',
            ]);

        if ($response->failed() || ! $response->json('access_token')) {
            throw new RuntimeException('Google token exchange failed.');
        }

        return $response->json();
    }

    /**
     * Fetch the user's OpenID Connect profile.
     *
     * @return array{sub:string,email:string,email_verified:bool,name:?string,picture:?string}
     */
    public function fetchProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)->acceptJson()->get(self::USERINFO_ENDPOINT);

        if ($response->failed() || ! $response->json('sub')) {
            throw new RuntimeException('Could not read the Google profile.');
        }

        return [
            'sub' => (string) $response->json('sub'),
            'email' => (string) $response->json('email'),
            'email_verified' => (bool) $response->json('email_verified'),
            'name' => $response->json('name'),
            'picture' => $response->json('picture'),
        ];
    }
}
