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

        // Enhanced bot detection with comprehensive patterns
        $isBot = self::detectBot($ua);

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

    /**
     * Enhanced bot detection with comprehensive patterns.
     * Detects search crawlers, social media scrapers, monitoring tools, and headless browsers.
     */
    private static function detectBot(string $ua): bool
    {
        // Empty or minimal UA strings (common bot signature)
        if (strlen($ua) < 10) {
            return true;
        }

        // Search engine crawlers
        $searchBots = '/googlebot|bingbot|slurp|yandexbot|baiduspider|sogou|duckduckbot|exabot|ia_archiver|archive.org|alexa|msnbot/i';

        // Social media crawlers and link preview tools
        $socialBots = '/facebookexternalhit|twitterbot|linkedinbot|pinterestbot|whatsapp|telegram|viber|slack|discord|instagram|snapchat|skype|line|wechat|iframely|embedly|getfbconnect|fetch|metabot|opengraph|oembedserver|graphstream/i';

        // HTTP clients and scripting libraries
        $httpClients = '/curl|wget|python-requests|httpx|aiohttp|requests|urllib|java|httpclient|http_client|axios|fetch|node-fetch|got|superagent|postman|insomnia|thunder|restsharp|okhttp|libcurl|perl/i';

        // Monitoring, uptime checking, and site scanning tools
        $monitoringTools = '/uptime|healthcheck|pingdom|monitoring|statuspage|checkly|synthetics|dareboost|gtmetrix|pagespeed|lighthouse|weborama|screaming|ahrefs|semrush|mj12bot|dotbot|majestic|grapeshot|hubspot|markmonitor/i';

        // Headless browsers and automation frameworks
        $headless = '/headless|selenium|phantomjs|rhino|jsdom|chrome\/headless|watir|htmlunit|casperjs|nightmare|puppeteer|playwright|cypress|webdriver|chromedriver|geckodriver/i';

        // Security scanners and vulnerability assessment tools
        $scanners = '/nessus|qualys|acunetix|nmap|nikto|masscan|metasploit|shodan|sqlmap|zap|burp|openvas|nessusbot|nuclei|dirbuster/i';

        // Analytics and tracking bots (but not legitimate ones like Google Analytics)
        $analytics = '/ips-agent|doubleclick|pagead|addthis|chartbeat|mixpanel|keen|amplitude|tealium|firebase|analytics|metrics|analytics_\w+/i';

        // Misc bots and crawlers
        $miscBots = '/bot|crawler|spider|scraper|indexer|agent|robot|scan|feed|harvest|fetch|download|copier|autoemail|checker|sitemap|autopilot|automation|nutch|grub|proxy/i';

        // Additional suspicious patterns
        $suspicious = [
            // Missing or suspicious user agents
            preg_match('/^-$|^$|^unknown$/i', $ua),
            // Spaces where none should be
            preg_match('/^[a-z]+\s+[a-z]+$/i', $ua) && strlen($ua) < 20,
            // Pure numbers
            preg_match('/^[\d\s.]+$/', $ua),
            // Very short (less than 15 chars) and contains numbers only
            strlen($ua) < 15 && preg_match('/^\d+$/', $ua),
        ];

        return (bool) (
            preg_match($searchBots, $ua) ||
            preg_match($socialBots, $ua) ||
            preg_match($httpClients, $ua) ||
            preg_match($monitoringTools, $ua) ||
            preg_match($headless, $ua) ||
            preg_match($scanners, $ua) ||
            preg_match($analytics, $ua) ||
            preg_match($miscBots, $ua) ||
            in_array(true, $suspicious, true)
        );
    }
}
