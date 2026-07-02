<?php

namespace App\Services\Analytics;

use App\Models\Webhook;
use Illuminate\Support\Facades\DB;

class RecordClick
{
    public function __construct(private GeoResolver $geo) {}

    /**
     * Persist a click event and bump the link's denormalized counter.
     * Runs AFTER the response is sent (see RedirectController), so it must
     * never throw into the redirect path.
     *
     * @param  array{link_id:int, ip:?string, ua:?string, referer:?string, language:?string}  $ctx
     */
    public function __invoke(array $ctx): void
    {
        try {
            $parsed = UaParser::parse($ctx['ua'] ?? null);
            $ip = (string) ($ctx['ip'] ?? '');
            $country = $ctx['country'] ?? $this->geo->country($ip);
            $region = $this->geo->region($ip);
            $city = $this->geo->city($ip);
            $refererHost = !empty($ctx['referer']) ? parse_url((string) $ctx['referer'], PHP_URL_HOST) : null;
            $ipHash = $ip !== '' ? hash('sha256', $ip.config('app.key')) : null;
            
            // Detect duplicate click: same link and ip_hash within last 30 seconds
            $isDuplicate = $this->isDuplicateClick($ctx['link_id'], $ipHash);

            DB::table('clicks')->insert([
                'link_id' => $ctx['link_id'],
                'ip_hash' => $ipHash,
                'country' => $country,
                'region' => $region,
                'city' => $city,
                'device' => $parsed['device'],
                'os' => $parsed['os'],
                'browser' => $parsed['browser'],
                'referer_host' => $refererHost,
                'language' => $ctx['language'] ?? null,
                'is_bot' => $parsed['is_bot'],
                'is_duplicate' => $isDuplicate,
                'created_at' => now(),
            ]);

            // Only count clicks towards the link's denormalized counter when
            // they are real (non-bot) and not marked as duplicates.
            if (!$parsed['is_bot'] && !$isDuplicate) {
                DB::table('links')->where('id', $ctx['link_id'])->update([
                    'clicks' => DB::raw('clicks + 1'),
                    'last_click_at' => now(),
                ]);
            } else {
                // Update last_click_at for visibility, but don't inflate counters for bots/duplicates.
                DB::table('links')->where('id', $ctx['link_id'])->update(['last_click_at' => now()]);
            }

            // Notify subscribed webhooks of real (non-bot) clicks.
            // Don't notify for duplicate clicks
            if (!$parsed['is_bot'] && !$isDuplicate && !empty($ctx['user_id'])) {
                Webhook::fire((int) $ctx['user_id'], 'link.clicked', [
                    'id' => $ctx['link_id'],
                    'alias' => $ctx['alias'] ?? null,
                    'short_url' => $ctx['short_url'] ?? null,
                    'target' => $ctx['target'] ?? null,
                    'country' => $country,
                    'device' => $parsed['device'],
                    'referer' => $refererHost,
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Check if this click is a duplicate (same link and IP within 30 seconds).
     */
    private function isDuplicateClick(int $linkId, ?string $ipHash): bool
    {
        if ($ipHash === null) {
            return false; // Can't detect duplicates without IP hash
        }

        // Check for clicks in the last 30 seconds with same link and IP
        $recentClick = DB::table('clicks')
            ->where('link_id', $linkId)
            ->where('ip_hash', $ipHash)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->limit(1)
            ->exists();

        return $recentClick;
    }
}
