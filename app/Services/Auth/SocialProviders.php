<?php

namespace App\Services\Auth;

/**
 * Registry of the supported social-login providers. Adding a provider here (plus
 * a config block and an AbstractOAuthProvider subclass) wires it into the routes,
 * the login page, the account connections, and the admin settings automatically.
 */
class SocialProviders
{
    /** key => [label, user-id column, OAuth class, button colour classes] */
    public const MAP = [
        'google' => ['label' => 'Google', 'column' => 'google_id', 'class' => GoogleOAuth::class, 'btn' => 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'],
        'github' => ['label' => 'GitHub', 'column' => 'github_id', 'class' => GitHubOAuth::class, 'btn' => 'border-slate-800 bg-slate-900 text-white hover:bg-slate-800'],
        'facebook' => ['label' => 'Facebook', 'column' => 'facebook_id', 'class' => FacebookOAuth::class, 'btn' => 'border-[#1877F2] bg-[#1877F2] text-white hover:bg-[#1466d2]'],
    ];

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::MAP);
    }

    public static function has(string $key): bool
    {
        return isset(self::MAP[$key]);
    }

    public static function label(string $key): string
    {
        return self::MAP[$key]['label'] ?? ucfirst($key);
    }

    public static function column(string $key): string
    {
        return self::MAP[$key]['column'];
    }

    public static function make(string $key, string $redirectUri): AbstractOAuthProvider
    {
        $class = self::MAP[$key]['class'];

        return new $class($redirectUri);
    }

    /**
     * Providers that are enabled + configured, for the login page / connections UI.
     *
     * @return array<string, array{label:string, btn:string}>
     */
    public static function enabled(): array
    {
        $out = [];
        foreach (self::MAP as $key => $meta) {
            if (self::make($key, '')->enabled()) {
                $out[$key] = ['label' => $meta['label'], 'btn' => $meta['btn']];
            }
        }

        return $out;
    }
}
