<?php

namespace App\Support;

/**
 * Social platforms available on bio pages, each mapped to a real brand icon
 * (committed under public/vendor/social) and brand color.
 */
class BioSocial
{
    /** key => [label, color] */
    public const PLATFORMS = [
        'instagram' => ['Instagram', '#E4405F'],
        'x' => ['X (Twitter)', '#000000'],
        'facebook' => ['Facebook', '#0866FF'],
        'youtube' => ['YouTube', '#FF0000'],
        'tiktok' => ['TikTok', '#000000'],
        'linkedin' => ['LinkedIn', '#0A66C2'],
        'whatsapp' => ['WhatsApp', '#25D366'],
        'telegram' => ['Telegram', '#26A5E4'],
        'discord' => ['Discord', '#5865F2'],
        'twitch' => ['Twitch', '#9146FF'],
        'spotify' => ['Spotify', '#1ED760'],
        'applemusic' => ['Apple Music', '#FA243C'],
        'soundcloud' => ['SoundCloud', '#FF5500'],
        'pinterest' => ['Pinterest', '#BD081C'],
        'snapchat' => ['Snapchat', '#FFB800'],
        'reddit' => ['Reddit', '#FF4500'],
        'threads' => ['Threads', '#000000'],
        'github' => ['GitHub', '#181717'],
        'dribbble' => ['Dribbble', '#EA4C89'],
        'behance' => ['Behance', '#1769FF'],
        'medium' => ['Medium', '#000000'],
        'substack' => ['Substack', '#FF6719'],
        'patreon' => ['Patreon', '#000000'],
        'vimeo' => ['Vimeo', '#1AB7EA'],
        'amazon' => ['Amazon', '#FF9900'],
        'paypal' => ['PayPal', '#003087'],
        'cashapp' => ['Cash App', '#00C244'],
        'venmo' => ['Venmo', '#008CFF'],
        'email' => ['Email', '#475569'],
        'phone' => ['Phone', '#475569'],
        'website' => ['Website', '#475569'],
    ];

    public static function label(string $key): string
    {
        return self::PLATFORMS[$key][0] ?? ucfirst($key);
    }

    public static function color(string $key): string
    {
        return self::PLATFORMS[$key][1] ?? '#475569';
    }

    public static function iconUrl(string $key): string
    {
        $slug = isset(self::PLATFORMS[$key]) ? $key : 'website';

        return asset('vendor/social/'.$slug.'.svg');
    }

    /** @return array<string, string> key => label, for the picker. */
    public static function options(): array
    {
        return array_map(fn ($v) => $v[0], self::PLATFORMS);
    }
}
