<?php

namespace App\Services\Ai;

use App\Models\Link;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Support\Carbon;

/**
 * On-demand AI summary of a single link's recent performance. Like the weekly
 * account insight, but scoped to one link and triggered from its analytics page.
 * The model only ever sees pre-computed figures, never the database.
 */
class LinkInsight
{
    public function __construct(
        private ClaudeClient $claude,
        private AnalyticsService $svc,
    ) {}

    public function write(Link $link): string
    {
        $figures = $this->figures($link);

        $system = 'You are a concise analytics assistant. In 2 to 3 sentences, summarise how this one '
            .'short link is performing and give one practical, specific suggestion. Use ONLY the figures '
            ."provided as JSON. Never invent numbers. Don't use em dashes.";

        $prompt = 'Link alias: '.$link->alias.PHP_EOL.PHP_EOL.'Figures (JSON): '.json_encode($figures);

        try {
            $text = $this->claude->text($system, $prompt, 220);
        } catch (\Throwable) {
            $text = '';
        }

        return $text !== '' ? $text : $this->fallback($figures);
    }

    /** @return array<string, mixed> */
    private function figures(Link $link): array
    {
        $scope = fn ($q) => $q->where('link_id', $link->id);
        $today = Carbon::today();

        $thisFrom = $today->copy()->subDays(6);
        $prevTo = $thisFrom->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays(6);

        $this7 = (int) ($this->svc->totals($scope, $thisFrom, $today)['clicks'] ?? 0);
        $prev7 = (int) ($this->svc->totals($scope, $prevFrom, $prevTo)['clicks'] ?? 0);

        // Top of each breakdown over the last 30 days.
        $dims = $this->svc->dimensions($scope, $today->copy()->subDays(29), $today, 1);

        return [
            'clicks_last_7d' => $this7,
            'clicks_prev_7d' => $prev7,
            'change_pct' => $prev7 > 0 ? (int) round(($this7 - $prev7) / $prev7 * 100) : null,
            'top_country' => $dims['country'][0]['label'] ?? null,
            'top_device' => $dims['device'][0]['label'] ?? null,
            'top_referer' => $dims['referer'][0]['label'] ?? null,
        ];
    }

    /** Deterministic phrasing if the narration call is unavailable. */
    private function fallback(array $f): string
    {
        $clicks = (int) ($f['clicks_last_7d'] ?? 0);

        if ($clicks === 0) {
            return 'This link has no clicks in the last 7 days yet. Share it on your strongest channel to get the first ones in.';
        }

        $s = number_format($clicks).' clicks in the last 7 days';
        if (($f['change_pct'] ?? null) !== null) {
            $pct = (int) $f['change_pct'];
            $s .= ' ('.($pct >= 0 ? 'up' : 'down').' '.abs($pct).'% versus the prior week)';
        }
        if (! empty($f['top_country'])) {
            $s .= ', led by '.$f['top_country'];
        }

        return ucfirst($s).'.';
    }
}
