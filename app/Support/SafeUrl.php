<?php

namespace App\Support;

/**
 * SSRF guard for operator-supplied outbound URLs (webhooks, future fetchers).
 * Rejects non-http(s) schemes and any host that is — or resolves to — a
 * loopback / private / reserved / link-local address (e.g. 127.0.0.1, 10.x,
 * 192.168.x, ::1, and the cloud metadata IP 169.254.169.254).
 *
 * Unresolvable hosts are allowed (they are not an SSRF vector — the request
 * simply fails); only hosts that resolve to internal space are blocked.
 */
class SafeUrl
{
    public static function isSafe(string $url): bool
    {
        $parts = parse_url(trim($url));
        if (! $parts || empty($parts['host']) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower(trim($parts['host'], '[]')); // strip IPv6 brackets

        if (in_array($host, ['localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback'], true)) {
            return false;
        }

        foreach (self::resolve($host) as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false; // private / reserved / loopback / link-local
            }
        }

        return true;
    }

    /** @return list<string> resolved IPs (the literal itself if $host is already an IP). */
    private static function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = [];
        foreach (@dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $r) {
            if (! empty($r['ip'])) {
                $ips[] = $r['ip'];
            }
            if (! empty($r['ipv6'])) {
                $ips[] = $r['ipv6'];
            }
        }
        if (! $ips && ($list = @gethostbynamel($host))) {
            $ips = $list;
        }

        return $ips; // empty = unresolvable -> not blocked
    }
}
