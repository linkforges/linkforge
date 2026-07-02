<?php

namespace App\Services\Analytics;

use GeoIp2\Database\Reader;
use GeoIp2\Model\City;
use Illuminate\Support\Facades\Http;

/**
 * Resolves a visitor's ISO country code.
 *
 * Order of preference:
 *   1. IP2Location HTTP API when configured with one or more API keys.
 *   2. A local MaxMind-format .mmdb (GeoLite2, or the no-account DB-IP / IPinfo
 *      country databases) at config('linkforge.geo.db_path').
 *
 * Registered as a singleton so the .mmdb reader is opened once per request.
 */
class GeoResolver
{
    private ?Reader $reader = null;

    private bool $readerResolved = false;

    private array $apiResponses = [];

    /** Memoized City lookup for the most recent IP (one mmdb read per IP). */
    private ?string $cityIp = null;

    private ?City $cityRec = null;

    public function country(?string $ip): ?string
    {
        return $this->normalize($this->countryFromApi($ip)) ?? $this->fromDatabase($ip);
    }

    /**
     * City name, when available. Prefers the IP2Location API first, then a local
     * City-level .mmdb (GeoLite2-City / DB-IP City). Null when neither resolves.
     */
    public function city(?string $ip): ?string
    {
        return $this->clean($this->cityFromApi($ip), 120) ?? $this->clean($this->cityRecord($ip)?->city->name, 120);
    }

    /**
     * Reverse geocode lat/lon to country/region/city using Nominatim (OpenStreetMap).
     * Returns an associative array with keys: country (ISO2), region, city.
     */
    public function reverseGeo(float $lat, float $lon): array
    {
        try {
            $resp = Http::withHeaders(['User-Agent' => 'LinkForge/1.0 +https://yourdomain.example'])->timeout(5)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'json',
                    'lat' => (string) $lat,
                    'lon' => (string) $lon,
                    'zoom' => 10,
                    'addressdetails' => 1,
                ]);
        } catch (\Throwable $e) {
            return ['country' => null, 'region' => null, 'city' => null];
        }

        if (!$resp->successful()) {
            return ['country' => null, 'region' => null, 'city' => null];
        }

        $payload = $resp->json();
        $address = is_array($payload['address'] ?? null) ? $payload['address'] : [];

        $country = $address['country_code'] ?? ($address['country_code'] ?? null);
        $country = is_string($country) ? strtoupper($country) : null;

        $region = $address['state'] ?? $address['region'] ?? null;
        $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['hamlet'] ?? null;

        return ['country' => $this->normalize($country), 'region' => $this->clean($region, 80), 'city' => $this->clean($city, 120)];
    }

    /** Region / state name, when available (same sources as city). */
    public function region(?string $ip): ?string
    {
        return $this->clean($this->regionFromApi($ip), 80) ?? $this->clean($this->cityRecord($ip)?->mostSpecificSubdivision->name, 80);
    }

    private function countryFromApi(?string $ip): ?string
    {
        $record = $this->apiRecord($ip);

        return is_array($record) ? $this->normalize($this->extractCountryCode($record)) : null;
    }

    private function cityFromApi(?string $ip): ?string
    {
        $record = $this->apiRecord($ip);

        return is_array($record) ? $this->clean($this->extractCityName($record), 120) : null;
    }

    private function regionFromApi(?string $ip): ?string
    {
        $record = $this->apiRecord($ip);

        return is_array($record) ? $this->clean($this->extractRegionName($record), 80) : null;
    }

    private function apiRecord(?string $ip): ?array
    {
        if (!$this->isLookupableIp($ip)) {
            return null;
        }

        $ip = trim((string) $ip);
        if (isset($this->apiResponses[$ip])) {
            return $this->apiResponses[$ip];
        }

        $keys = $this->apiKeys();
        if ($keys === []) {
            return $this->apiResponses[$ip] = null;
        }

        shuffle($keys);
        foreach ($keys as $key) {
            try {
                $response = Http::timeout(5)->accept('application/json')->get($this->ip2LocationUrl($ip, $key));
            } catch (\Throwable $e) {
                continue;
            }

            if (!$response->successful()) {
                continue;
            }

            $payload = $response->json();
            if (!is_array($payload)) {
                continue;
            }

            $countryCode = $this->extractCountryCode($payload);
            if ($countryCode === null || $this->normalize($countryCode) === null) {
                continue;
            }

            $payload['country_code'] = $this->normalize($countryCode);
            return $this->apiResponses[$ip] = $payload;
        }

        return $this->apiResponses[$ip] = null;
    }

    private function apiKeys(): array
    {
        $raw = config('linkforge.geo.ip2location_keys');
        if (is_array($raw)) {
            $raw = implode(',', $raw);
        }

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn (?string $key) => $key !== ''));
    }

    private function ip2LocationUrl(string $ip, string $key): string
    {
        return 'https://api.ip2location.com/v2/?ip='.urlencode($ip).'&key='.urlencode($key).'&format=json';
    }

    private function isLookupableIp(?string $ip): bool
    {
        if (!$ip) {
            return false;
        }

        $ip = trim((string) $ip);
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return false;
        }

        return (bool) filter_var($ip, FILTER_VALIDATE_IP);
    }

    private function normalize(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        // Cloudflare uses XX (unknown) and T1 (Tor) for non-countries.
        return preg_match('/^[A-Z]{2}$/', $code) && !in_array($code, ['XX', 'T1'], true)
            ? $code
            : null;
    }

    private function fromDatabase(?string $ip): ?string
    {
        if (!$ip || $ip === '127.0.0.1' || $ip === '::1') {
            return null;
        }

        $reader = $this->reader();
        if (!$reader) {
            return null;
        }

        // A City database only answers ->city() — calling ->country() on it throws
        // BadMethodCallException — while a Country database only answers ->country().
        // The City record carries the country too, so resolve from it first (this is
        // what makes "Top countries" + the map work on a City DB), then fall back to
        // ->country() for country-only databases.
        $isoCode = $this->cityRecord($ip)?->country->isoCode;
        if ($isoCode) {
            return $isoCode;
        }

        try {
            return $reader->country($ip)->country->isoCode;
        } catch (\Throwable $e) {
            return null; // address not in DB / invalid — degrade gracefully
        }
    }

    /**
     * Look up the full City record once per IP (memoized within the request).
     * Returns null on a country-only database, a missing IP, or any failure.
     */
    private function cityRecord(?string $ip): ?City
    {
        if ($ip === $this->cityIp) {
            return $this->cityRec;
        }
        $this->cityIp = $ip;
        $this->cityRec = null;

        if (!$ip || $ip === '127.0.0.1' || $ip === '::1') {
            return null;
        }
        $reader = $this->reader();
        if (!$reader) {
            return null;
        }

        try {
            $this->cityRec = $reader->city($ip);
        } catch (\Throwable $e) {
            $this->cityRec = null; // country-only DB / not found / invalid
        }

        return $this->cityRec;
    }

    /** Trim a place name, drop empties, and cap to the column width. */
    private function clean(?string $value, int $max): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function reader(): ?Reader
    {
        if ($this->readerResolved) {
            return $this->reader;
        }
        $this->readerResolved = true;

        $path = $this->databasePath();
        if ($path) {
            try {
                $this->reader = new Reader($path);
            } catch (\Throwable $e) {
                $this->reader = null;
            }
        }

        return $this->reader;
    }

    private function databasePath(): ?string
    {
        // 1. Explicit override via config/env.
        $path = (string) config('linkforge.geo.db_path');
        if ($path !== '') {
            // Resolve relative paths from the project root.
            $isAbsolute = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path);
            if (!$isAbsolute) {
                $path = base_path($path);
            }
            if (is_file($path)) {
                return $path;
            }
        }

        // 2. Operator-managed / auto-updated DB (the in-app updater writes here, and
        //    it takes precedence over the bundled seed so a City upgrade wins).
        $managed = (glob(storage_path('app/geoip/*.mmdb')) ?: [])[0] ?? null;
        if ($managed) {
            return $managed;
        }

        // 3. The small country DB bundled with the app, so country geo + the map
        //    work out of the box with zero setup.
        return (glob(base_path('database/geoip/*.mmdb')) ?: [])[0] ?? null;
    }

    private function extractCountryCode(array $record): ?string
    {
        $code = $record['country_code'] ?? $record['country'] ?? $record['countryCode'] ?? null;

        if (is_array($code)) {
            $code = $code['iso_code'] ?? $code['code'] ?? null;
        }

        return is_string($code) ? trim($code) : null;
    }

    private function extractRegionName(array $record): ?string
    {
        $region = $record['region_name'] ?? $record['region'] ?? $record['regionName'] ?? null;

        if (is_array($region)) {
            $region = $region['name'] ?? null;
        }

        return is_string($region) ? trim($region) : null;
    }

    private function extractCityName(array $record): ?string
    {
        $city = $record['city_name'] ?? $record['city'] ?? $record['cityName'] ?? null;

        if (is_array($city)) {
            $city = $city['name'] ?? null;
        }

        return is_string($city) ? trim($city) : null;
    }
}
