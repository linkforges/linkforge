<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Shared OAuth 2.0 authorization-code flow, spoken directly over HTTP (no SDK),
 * for the app's social logins. Concrete providers supply their endpoints, scope
 * and profile mapping; the flow itself is identical:
 *   1. authUrl()      -> send the user to the provider's consent screen (with CSRF state)
 *   2. exchangeCode() -> trade the returned code for an access token
 *   3. fetchProfile() -> read the user's profile, normalised to a common shape
 */
abstract class AbstractOAuthProvider
{
    public function __construct(protected readonly string $redirectUri) {}

    /** Config/service key, e.g. "google" -> config('services.google.*'). */
    abstract protected function key(): string;

    abstract protected function authEndpoint(): string;

    abstract protected function tokenEndpoint(): string;

    abstract protected function scope(): string;

    /**
     * Read the authenticated user's profile.
     *
     * @return array{id:string, email:string, email_verified:bool, name:?string, avatar:?string}
     */
    abstract public function fetchProfile(string $accessToken): array;

    /** Provider-specific extra parameters for the authorization URL. */
    protected function authParams(): array
    {
        return [];
    }

    /** Provider-specific parameters for the token exchange. */
    protected function tokenParams(string $code): array
    {
        return [
            'code' => $code,
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ];
    }

    public function enabled(): bool
    {
        return (bool) config("services.{$this->key()}.enabled") && $this->configured();
    }

    public function configured(): bool
    {
        return ! empty($this->clientId()) && ! empty($this->clientSecret());
    }

    public function authUrl(string $state): string
    {
        return $this->authEndpoint().'?'.http_build_query(array_merge([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => $this->scope(),
            'state' => $state,
        ], $this->authParams()));
    }

    /** @return array{access_token:string,...} */
    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()->acceptJson()->post($this->tokenEndpoint(), $this->tokenParams($code));

        if ($response->failed() || ! $response->json('access_token')) {
            throw new RuntimeException(ucfirst($this->key()).' token exchange failed.');
        }

        return $response->json();
    }

    protected function clientId(): ?string
    {
        return config("services.{$this->key()}.client_id");
    }

    protected function clientSecret(): ?string
    {
        return config("services.{$this->key()}.client_secret");
    }
}
