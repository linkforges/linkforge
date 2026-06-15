<?php

namespace App\Services\Linking;

use App\Models\Domain;
use Illuminate\Support\Facades\Cache;

class DomainResolver
{
    private const TTL = 3600;

    /** The default system short-domain (where shared / free links live). */
    public function default(): ?Domain
    {
        return Cache::remember('lf:domain:default', self::TTL, fn () => Domain::where('is_default', true)->first());
    }

    public function byHost(string $host): ?Domain
    {
        $host = strtolower($host);

        return Cache::remember("lf:domain:host:{$host}", self::TTL, fn () => Domain::where('host', $host)->first());
    }

    /** Resolve the incoming Host header to a domain, falling back to the default. */
    public function resolve(string $host): ?Domain
    {
        return $this->byHost($host) ?: $this->default();
    }

    public function forget(string $host): void
    {
        Cache::forget('lf:domain:default');
        Cache::forget('lf:domain:host:'.strtolower($host));
    }
}
