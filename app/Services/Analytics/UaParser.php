<?php

namespace App\Services\Analytics;

class UaParser
{
    /**
     * Bucket a user-agent string into device / os / browser for analytics.
     *
     * @return array{device:string, os:string, browser:string, is_bot:bool}
     */
    public static function parse(?string $ua): array
    {
        $ua = (string) $ua;

        $isBot = (bool) preg_match(
            '/bot|crawl|spider|slurp|bing|google|facebookexternalhit|whatsapp|telegram|preview|monitor|curl|wget|headless|python-requests|axios|httpclient|uptime/i',
            $ua
        );

        $device = 'desktop';
        if (preg_match('/ipad|tablet|playbook|silk|android(?!.*mobile)/i', $ua)) {
            $device = 'tablet';
        } elseif (preg_match('/mobile|iphone|ipod|windows phone|blackberry|bb10|opera mini|iemobile/i', $ua)) {
            $device = 'mobile';
        }
        if ($isBot) {
            $device = 'bot';
        }

        $os = 'Other';
        foreach ([
            'iOS' => '/iphone|ipad|ipod/i',
            'Android' => '/android/i',
            'Windows' => '/windows nt/i',
            'macOS' => '/mac os x|macintosh/i',
            'Chrome OS' => '/cros/i',
            'Linux' => '/linux/i',
        ] as $name => $re) {
            if (preg_match($re, $ua)) {
                $os = $name;
                break;
            }
        }

        // Order matters: Edge/Opera/Samsung UAs also contain "chrome"; Chrome UAs contain "safari".
        $browser = 'Other';
        foreach ([
            'Edge' => '/edg/i',
            'Opera' => '/opr|opera/i',
            'Samsung' => '/samsungbrowser/i',
            'Firefox' => '/firefox|fxios/i',
            'Chrome' => '/chrome|crios/i',
            'Safari' => '/safari/i',
        ] as $name => $re) {
            if (preg_match($re, $ua)) {
                $browser = $name;
                break;
            }
        }

        return ['device' => $device, 'os' => $os, 'browser' => $browser, 'is_bot' => $isBot];
    }
}
