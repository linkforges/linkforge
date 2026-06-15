<?php

namespace App\Support;

/**
 * Maps a stored Accept-Language value (e.g. "en", "en-US", "pt-BR") to a
 * human-readable language name for the analytics breakdowns.
 */
class Locales
{
    private const NAMES = [
        'en' => 'English', 'es' => 'Spanish', 'pt' => 'Portuguese', 'fr' => 'French',
        'de' => 'German', 'it' => 'Italian', 'nl' => 'Dutch', 'ru' => 'Russian',
        'pl' => 'Polish', 'tr' => 'Turkish', 'ar' => 'Arabic', 'fa' => 'Persian',
        'he' => 'Hebrew', 'hi' => 'Hindi', 'bn' => 'Bengali', 'ur' => 'Urdu',
        'id' => 'Indonesian', 'ms' => 'Malay', 'th' => 'Thai', 'vi' => 'Vietnamese',
        'ja' => 'Japanese', 'ko' => 'Korean', 'zh' => 'Chinese', 'uk' => 'Ukrainian',
        'cs' => 'Czech', 'sk' => 'Slovak', 'ro' => 'Romanian', 'hu' => 'Hungarian',
        'el' => 'Greek', 'sv' => 'Swedish', 'da' => 'Danish', 'fi' => 'Finnish',
        'no' => 'Norwegian', 'nb' => 'Norwegian', 'bg' => 'Bulgarian', 'hr' => 'Croatian',
        'sr' => 'Serbian', 'sl' => 'Slovenian', 'lt' => 'Lithuanian', 'lv' => 'Latvian',
        'et' => 'Estonian', 'ca' => 'Catalan', 'fil' => 'Filipino', 'sw' => 'Swahili',
    ];

    public static function name(?string $code): string
    {
        $code = trim((string) $code);
        if ($code === '') {
            return 'Unknown';
        }

        $primary = strtolower(explode('-', str_replace('_', '-', $code))[0]);

        return self::NAMES[$primary] ?? strtoupper($code);
    }

    /**
     * UI locales the app can render: English plus every lang/{code}.json the
     * operator has added. Returns code => display name, sorted by name.
     *
     * @return array<string, string>
     */
    public static function available(): array
    {
        $out = ['en' => self::name('en')];
        foreach (glob(lang_path('*.json')) ?: [] as $file) {
            $code = strtolower(basename($file, '.json'));
            $out[$code] = self::name($code);
        }
        asort($out);

        return $out;
    }

    public static function isAvailable(?string $code): bool
    {
        return $code !== null && array_key_exists(strtolower($code), self::available());
    }

    /**
     * The full catalogue of language codes the app knows a name for — used to
     * populate the "add a language" picker in the admin. Returns code => name,
     * sorted by name.
     *
     * @return array<string, string>
     */
    public static function catalog(): array
    {
        $out = self::NAMES;
        asort($out);

        return $out;
    }

    /** Right-to-left primary language codes. */
    public static function isRtl(?string $code): bool
    {
        $primary = strtolower(explode('-', str_replace('_', '-', (string) $code))[0]);

        return in_array($primary, ['ar', 'fa', 'he', 'ur'], true);
    }
}
