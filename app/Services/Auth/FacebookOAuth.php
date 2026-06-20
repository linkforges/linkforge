<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * "Sign in with Facebook" via OAuth 2.0 (Facebook Login).
 * Docs: https://developers.facebook.com/docs/facebook-login/guides/advanced/manual-flow
 */
class FacebookOAuth extends AbstractOAuthProvider
{
    private const VERSION = 'v19.0';

    protected function key(): string
    {
        return 'facebook';
    }

    protected function authEndpoint(): string
    {
        return 'https://www.facebook.com/'.self::VERSION.'/dialog/oauth';
    }

    protected function tokenEndpoint(): string
    {
        return 'https://graph.facebook.com/'.self::VERSION.'/oauth/access_token';
    }

    protected function scope(): string
    {
        return 'email';
    }

    /** Facebook's token endpoint takes the four params without a grant_type. */
    protected function tokenParams(string $code): array
    {
        return [
            'code' => $code,
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $this->redirectUri,
        ];
    }

    public function fetchProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)->acceptJson()
            ->get('https://graph.facebook.com/'.self::VERSION.'/me', ['fields' => 'id,name,email']);

        if ($response->failed() || ! $response->json('id')) {
            throw new RuntimeException('Could not read the Facebook profile.');
        }

        $email = (string) $response->json('email');

        return [
            'id' => (string) $response->json('id'),
            'email' => $email,
            'email_verified' => $email !== '', // Facebook only returns verified emails
            'name' => $response->json('name'),
            'avatar' => null,
        ];
    }
}
