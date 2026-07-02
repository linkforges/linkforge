<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\BioPage;
use App\Models\Link;
use App\Models\Setting;
use App\Services\Analytics\BioAnalytics;
use App\Services\Analytics\GeoResolver;
use App\Services\Analytics\RecordClick;
use App\Services\Analytics\UaParser;
use App\Services\Billing\PlanGate;
use App\Services\Linking\DomainResolver;
use App\Services\Linking\RuleResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RedirectController extends Controller
{
    public function __construct(private DomainResolver $domains) {}

    /**
     * The redirect hot-path. Registered as the route fallback so it only fires
     * for paths no other route claims. Kept lean: one cached lookup, guard
     * checks, then an after-response click record.
     */
    public function handle(Request $request)
    {
        $alias = trim($request->path(), '/');

        if ($alias === '' || str_contains($alias, '/')) {
            abort(404);
        }

        $domain = $this->domains->resolve($request->getHost());
        $link = $domain ? $this->lookup($domain->id, $alias) : null;

        if (! $link) {
            // A slug can also be a published bio page (shared root namespace).
            return $this->renderBio($request, $alias) ?? abort(404);
        }

        if (! $link->is_active) {
            return response()->view('redirect.unavailable', ['reason' => 'inactive'], 410);
        }
        if ($link->isExpired()) {
            return response()->view('redirect.unavailable', ['reason' => 'expired'], 410);
        }
        if ($link->isOverLimit()) {
            return response()->view('redirect.unavailable', ['reason' => 'limit'], 410);
        }
        if ($link->safety_status === 'blocked') {
            return response()->view('redirect.blocked', ['link' => $link], 403);
        }
        if ($link->password && ! $request->session()->get("lf_unlocked:{$link->id}")) {
            return response()->view('redirect.password', ['alias' => $alias, 'error' => null]);
        }

        // Smart routing: geo / device / os / language / time targeting + weighted rotation.
        // Geo resolution prefers the visitor's real client IP from trusted proxy headers
        // when available, otherwise falls back to the request IP.
        $ip = $this->resolveVisitorIp($request);

        // Lightweight per-IP per-link rate limiting to mitigate abusive scraping.
        if ($ip) {
            try {
                $key = "link:{$link->id}:ip:".hash('sha256', $ip.config('app.key')).':hits';
                $hits = Cache::increment($key);
                if ($hits === 1) {
                    Cache::put($key, 1, 60); // expire in 60s
                }
                if ($hits !== false && $hits > 60) {
                    abort(429);
                }
            } catch (\Throwable $e) {
                // Fail open on cache errors
            }
        }
        $parsed = UaParser::parse($request->userAgent());

        // Quickly block known bots when the link explicitly requests it.
        if ($parsed['is_bot'] && ($link->block_bots ?? false)) {
            return response()->view('redirect.blocked', ['link' => $link], 403);
        }

        // Quick referrer-based blocking (host match / wildcard suffix support).
        $referer = $request->headers->get('referer');
        $refererHost = $referer ? parse_url((string) $referer, PHP_URL_HOST) : null;
        if ($refererHost && ! empty($link->blocked_referrers)) {
            $host = strtolower(preg_replace('/^www\./', '', $refererHost));
            foreach ((array) $link->blocked_referrers as $blocked) {
                $b = strtolower(trim((string) $blocked));
                if ($b === '') {
                    continue;
                }
                // wildcard: *.example.com -> match any subdomain or example.com
                if (str_starts_with($b, '*.') && str_ends_with($host, substr($b, 1))) {
                    return response()->view('redirect.blocked', ['link' => $link], 403);
                }
                // suffix match
                if (str_ends_with($host, $b) || $host === $b) {
                    return response()->view('redirect.blocked', ['link' => $link], 403);
                }
            }
        }

        $country = $this->resolveVisitorCountry($request, $ip);
        $routeCtx = [
            'country' => $country,
            'device' => $parsed['device'],
            'os' => $parsed['os'],
            'language' => $request->getPreferredLanguage() ? substr((string) $request->getPreferredLanguage(), 0, 5) : null,
            'now' => now(),
            'referer_host' => $refererHost,
        ];

        $target = $link->appendParams(app(RuleResolver::class)->resolve($link, $routeCtx));

        $ctx = [
            'link_id' => $link->id,
            'user_id' => $link->user_id,
            'alias' => $link->alias,
            'short_url' => $request->url(),
            'target' => $target,
            'ip' => $ip,
            'country' => $country,
            'ua' => $request->userAgent(),
            'referer' => $request->headers->get('referer'),
            'language' => substr((string) $request->getPreferredLanguage(), 0, 10) ?: null,
        ];
        app()->terminating(fn () => app(RecordClick::class)($ctx));

        // Render the interstitial splash for non-direct link types, OR whenever the link
        // has retargeting pixels attached. Pixels are client-side scripts and need an HTML
        // page to fire on, so even a "direct" link must pass through the splash to track.
        $pixels = $link->relationLoaded('pixels') ? $link->pixels : $link->pixels()->get();

        // Mobile deep link (Pro feature): try to open the native app, with a web fallback.
        if ($appUrl = $this->resolveDeepLink($link, $routeCtx['os'])) {
            return response()->view('redirect.deeplink', [
                'target' => $target,
                'appUrl' => $appUrl,
                'pixels' => $pixels,
                'link_id' => $link->id,
            ]);
        }

        $ad = $this->resolveAd($link);
        if ($link->type !== 'direct' || $pixels->isNotEmpty() || $ad) {
            return response()->view('redirect.splash', [
                'target' => $target,
                'pixels' => $pixels,
                'ad' => $ad,
                'skipSeconds' => $ad ? max(0, (int) Setting::get('ads_skip_seconds', 5)) : 0,
                'link_id' => $link->id,
            ]);
        }

        // For direct fast-path redirects, ensure a strict referrer policy header
        // is present and perform a lightweight redirect.
        return redirect()->away($target, 302)->header('Referrer-Policy', 'origin-when-cross-origin');
    }

    private function resolveVisitorIp(Request $request): ?string
    {
        $ip = $this->firstValidIp($request->header('CF-Connecting-IP'));
        if ($ip) {
            return $ip;
        }

        $ip = $this->firstValidIp($request->header('X-Real-IP'));
        if ($ip) {
            return $ip;
        }

        $forwards = $request->header('X-Forwarded-For');
        if ($forwards) {
            foreach (explode(',', $forwards) as $candidate) {
                $candidate = trim((string) $candidate);
                if ($this->isValidIp($candidate)) {
                    return $candidate;
                }
            }
        }

        return $this->firstValidIp($request->ip());
    }

    private const COUNTRY_HEADER_KEYS = ['CF-IPCountry', 'X-Country-Code', 'X-Geo-Country', 'X-Geo-Country-Code'];

    private const VALID_COUNTRY_CODES = [
        'AF' => true, 'AX' => true, 'AL' => true, 'DZ' => true, 'AS' => true, 'AD' => true, 'AO' => true,
        'AI' => true, 'AQ' => true, 'AG' => true, 'AR' => true, 'AM' => true, 'AW' => true, 'AU' => true,
        'AT' => true, 'AZ' => true, 'BS' => true, 'BH' => true, 'BD' => true, 'BB' => true, 'BY' => true,
        'BE' => true, 'BZ' => true, 'BJ' => true, 'BM' => true, 'BT' => true, 'BO' => true, 'BQ' => true,
        'BA' => true, 'BW' => true, 'BV' => true, 'BR' => true, 'IO' => true, 'BN' => true, 'BG' => true,
        'BF' => true, 'BI' => true, 'CV' => true, 'KH' => true, 'CM' => true, 'CA' => true, 'KY' => true,
        'CF' => true, 'TD' => true, 'CL' => true, 'CN' => true, 'CX' => true, 'CC' => true, 'CO' => true,
        'KM' => true, 'CG' => true, 'CD' => true, 'CK' => true, 'CR' => true, 'CI' => true, 'HR' => true,
        'CU' => true, 'CW' => true, 'CY' => true, 'CZ' => true, 'DK' => true, 'DJ' => true, 'DM' => true,
        'DO' => true, 'EC' => true, 'EG' => true, 'SV' => true, 'GQ' => true, 'ER' => true, 'EE' => true,
        'ET' => true, 'FK' => true, 'FO' => true, 'FJ' => true, 'FI' => true, 'FR' => true, 'GF' => true,
        'PF' => true, 'TF' => true, 'GA' => true, 'GM' => true, 'GE' => true, 'DE' => true, 'GH' => true,
        'GI' => true, 'GR' => true, 'GL' => true, 'GD' => true, 'GP' => true, 'GU' => true, 'GT' => true,
        'GG' => true, 'GN' => true, 'GW' => true, 'GY' => true, 'HT' => true, 'HM' => true, 'VA' => true,
        'HN' => true, 'HK' => true, 'HU' => true, 'IS' => true, 'IN' => true, 'ID' => true, 'IR' => true,
        'IQ' => true, 'IE' => true, 'IM' => true, 'IL' => true, 'IT' => true, 'JM' => true, 'JP' => true,
        'JE' => true, 'JO' => true, 'KZ' => true, 'KE' => true, 'KI' => true, 'KP' => true, 'KR' => true,
        'KW' => true, 'KG' => true, 'LA' => true, 'LV' => true, 'LB' => true, 'LS' => true, 'LR' => true,
        'LY' => true, 'LI' => true, 'LT' => true, 'LU' => true, 'MO' => true, 'MK' => true, 'MG' => true,
        'MW' => true, 'MY' => true, 'MV' => true, 'ML' => true, 'MT' => true, 'MH' => true, 'MQ' => true,
        'MR' => true, 'MU' => true, 'YT' => true, 'MX' => true, 'FM' => true, 'MD' => true, 'MC' => true,
        'MN' => true, 'ME' => true, 'MS' => true, 'MA' => true, 'MZ' => true, 'MM' => true, 'NA' => true,
        'NR' => true, 'NP' => true, 'NL' => true, 'NC' => true, 'NZ' => true, 'NI' => true, 'NE' => true,
        'NG' => true, 'NU' => true, 'NF' => true, 'MP' => true, 'NO' => true, 'OM' => true, 'PK' => true,
        'PW' => true, 'PS' => true, 'PA' => true, 'PG' => true, 'PY' => true, 'PE' => true, 'PH' => true,
        'PN' => true, 'PL' => true, 'PT' => true, 'PR' => true, 'QA' => true, 'RE' => true, 'RO' => true,
        'RU' => true, 'RW' => true, 'BL' => true, 'SH' => true, 'KN' => true, 'LC' => true, 'MF' => true,
        'PM' => true, 'VC' => true, 'WS' => true, 'SM' => true, 'ST' => true, 'SA' => true, 'SN' => true,
        'RS' => true, 'SC' => true, 'SL' => true, 'SG' => true, 'SX' => true, 'SK' => true, 'SI' => true,
        'SB' => true, 'SO' => true, 'ZA' => true, 'GS' => true, 'SS' => true, 'ES' => true, 'LK' => true,
        'SD' => true, 'SR' => true, 'SJ' => true, 'SE' => true, 'CH' => true, 'SY' => true, 'TW' => true,
        'TJ' => true, 'TZ' => true, 'TH' => true, 'TL' => true, 'TG' => true, 'TK' => true, 'TO' => true,
        'TT' => true, 'TN' => true, 'TR' => true, 'TM' => true, 'TC' => true, 'TV' => true, 'UG' => true,
        'UA' => true, 'AE' => true, 'GB' => true, 'US' => true, 'UM' => true, 'UY' => true, 'UZ' => true,
        'VU' => true, 'VE' => true, 'VN' => true, 'VG' => true, 'VI' => true, 'WF' => true, 'EH' => true,
        'YE' => true, 'ZM' => true, 'ZW' => true,
    ];

    private function resolveVisitorCountry(Request $request, ?string $resolvedIp): ?string
    {
        $country = app(GeoResolver::class)->country($resolvedIp);
        if ($country !== null) {
            return $country;
        }

        return $this->headerCountryCode($request);
    }

    private function headerCountryCode(Request $request): ?string
    {
        foreach (self::COUNTRY_HEADER_KEYS as $header) {
            $value = $request->header($header);
            if ($value && ($normalized = $this->normalizeCountryCode($value))) {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeCountryCode(string $code): ?string
    {
        $code = strtoupper(trim($code));

        return isset(self::VALID_COUNTRY_CODES[$code]) ? $code : null;
    }

    private function firstValidIp(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $value = trim((string) $value);

        return $this->isValidIp($value) ? $value : null;
    }

    private function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false && ! in_array($ip, ['127.0.0.1', '::1'], true);
    }

    /**
     * The app deep-link URI to attempt for this visitor, or null. Only fires on
     * iOS/Android, when the link has a target for that OS, and the owner's plan
     * includes deep links.
     */
    private function resolveDeepLink(Link $link, ?string $os): ?string
    {
        if (! in_array($os, ['iOS', 'Android'], true) || ! $link->hasDeepLinks()) {
            return null;
        }

        $owner = $link->relationLoaded('user') ? $link->user : $link->user()->first();
        if (! $owner || ! app(PlanGate::class)->allows($owner, 'deep_links')) {
            return null;
        }

        return $link->deepLinkFor($os);
    }

    /**
     * Which ad (if any) shows on the interstitial for this link:
     *   - owner on an ad-free plan  -> their OWN ad code (the member monetizes their traffic)
     *   - otherwise (free user)     -> the operator's ad unit (the operator monetizes the free tier)
     * Returns a render spec ['code'=>?, 'image'=>?, 'url'=>?, 'own'=>bool] or null.
     */
    private function resolveAd(Link $link): ?array
    {
        if (Setting::get('ads_enabled') !== '1') {
            return null;
        }

        $owner = $link->relationLoaded('user') ? $link->user : $link->user()->first();
        if (! $owner) {
            return null;
        }

        // Premium / ad-free: never show operator ads; show the member's own ad slots if set.
        if (app(PlanGate::class)->allows($owner, 'ad_free')) {
            $slots = MonetizationController::slotsFor($owner);
            $slots = array_values(array_filter($slots)); // drop the form padding / empties

            return $slots ? ['own' => true, 'slots' => $slots] : null;
        }

        // Free tier: the operator's ad. Count an impression after the response.
        $op = Advertisement::activeFor('interstitial');
        if (! $op) {
            return null;
        }
        app()->terminating(fn () => $op->recordImpression());

        if ($op->code) {
            return ['code' => $op->code, 'own' => false];
        }

        return $op->imageUrl() ? ['image' => $op->imageUrl(), 'url' => $op->target_url, 'own' => false] : null;
    }

    /** Verify the password for a protected link and unlock it for the session. */
    public function unlock(Request $request, string $alias)
    {
        $domain = $this->domains->resolve($request->getHost());
        $link = $domain ? Link::where('domain_id', $domain->id)->where('alias', $alias)->first() : null;

        if (! $link || ! $link->password) {
            abort(404);
        }

        if (! Hash::check((string) $request->input('password'), $link->password)) {
            return response()->view('redirect.password', [
                'alias' => $alias,
                'error' => 'Incorrect password. Please try again.',
            ], 422);
        }

        $request->session()->put("lf_unlocked:{$link->id}", true);

        return redirect('/'.$alias);
    }

    private function renderBio(Request $request, string $slug)
    {
        $page = BioPage::where('slug', $slug)->where('is_published', true)
            ->with(['blocks' => fn ($q) => $q->where('is_active', true)->orderBy('sort')])
            ->first();

        if (! $page) {
            return null;
        }

        // Password gate.
        if ($page->setting('password') && ! session("bio_unlocked.{$page->id}")) {
            return response()->view('bio.gate', ['page' => $page, 'mode' => 'password', 'error' => session('bio_gate_error')]);
        }

        // Sensitive-content warning.
        if ($page->setting('sensitive') && ! session("bio_ack.{$page->id}")) {
            return response()->view('bio.gate', ['page' => $page, 'mode' => 'sensitive', 'error' => null]);
        }

        $bio = app(BioAnalytics::class);
        app()->terminating(function () use ($page, $bio, $request) {
            DB::table('bio_pages')->where('id', $page->id)->increment('views');
            $bio->record($page->id, null, 'view', $request);
        });

        return response()->view('bio.show', ['page' => $page]);
    }

    private function lookup(int $domainId, string $alias): ?Link
    {
        return Cache::remember(
            Link::cacheKey($domainId, $alias),
            300,
            // Eager-load the owner + plan so monetization's resolveAd() reads them from the
            // warmed cache payload instead of querying users + plans on every redirect.
            fn () => Link::with(['rules', 'pixels', 'user.plan'])->where('domain_id', $domainId)->where('alias', $alias)->first()
        );
    }
}
