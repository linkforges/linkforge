<?php

namespace App\Support;

/**
 * Resolves an analytics label (OS / browser / device) to a real brand glyph.
 *
 * All glyphs come from real icon packages, extracted to resources/data/icons.php
 * at build time: simple-icons (brand logos), bootstrap-icons (Windows + Edge),
 * lucide-static (device + globe). Nothing is hand-drawn.
 */
class BrandIcon
{
    /** @var array<string, array{mode:string, vb:string, color?:string, body:string}>|null */
    private static ?array $icons = null;

    private const OS = [
        'iOS' => 'apple', 'macOS' => 'apple', 'Android' => 'android', 'Windows' => 'windows',
        'Chrome OS' => 'googlechrome', 'Linux' => 'linux', 'Ubuntu' => 'ubuntu',
    ];

    private const BROWSER = [
        'Chrome' => 'googlechrome', 'Firefox' => 'firefoxbrowser', 'Safari' => 'safari',
        'Edge' => 'edge', 'Opera' => 'opera', 'Samsung' => 'samsung', 'Brave' => 'brave',
    ];

    private const DEVICE = [
        'desktop' => 'monitor', 'mobile' => 'smartphone', 'tablet' => 'tablet', 'bot' => 'bot',
    ];

    public static function os(string $label): array
    {
        return self::get(self::OS[$label] ?? 'globe');
    }

    public static function browser(string $label): array
    {
        return self::get(self::BROWSER[$label] ?? 'globe');
    }

    public static function device(string $label): array
    {
        return self::get(self::DEVICE[strtolower($label)] ?? 'globe');
    }

    /** @return array{mode:string, vb:string, color:string, body:string} */
    public static function get(string $slug): array
    {
        $icons = self::$icons ??= require resource_path('data/icons.php');
        $icon = $icons[$slug] ?? $icons['globe'];

        return $icon + ['color' => 'currentColor'];
    }
}
