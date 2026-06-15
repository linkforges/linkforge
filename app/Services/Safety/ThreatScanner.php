<?php

namespace App\Services\Safety;

use Illuminate\Support\Facades\Http;

/**
 * Multi-source threat intelligence. Each provider is config-gated and fully
 * resilient: a disabled or failing provider returns null and is ignored, so
 * the scan never breaks link creation. With no providers configured the
 * verdict is "safe" (the local LinkSafety screen still applies separately).
 */
class ThreatScanner
{
    /** @return array{status:string, score:int, scans:list<array{provider:string, verdict:string, raw:mixed}>} */
    public function scan(string $url): array
    {
        $verdicts = array_values(array_filter([
            $this->urlhaus($url),
            $this->virusTotal($url),
        ]));

        $malicious = false;
        $suspicious = false;
        foreach ($verdicts as $v) {
            $malicious = $malicious || $v['verdict'] === 'malicious';
            $suspicious = $suspicious || $v['verdict'] === 'suspicious';
        }

        return [
            'status' => $malicious ? 'blocked' : ($suspicious ? 'flagged' : 'safe'),
            'score' => $malicious ? 100 : ($suspicious ? 50 : 0),
            'scans' => $verdicts,
        ];
    }

    private function urlhaus(string $url): ?array
    {
        if (! config('linkforge.safety.providers.urlhaus')) {
            return null;
        }

        try {
            $data = Http::timeout(4)->asForm()
                ->post('https://urlhaus-api.abuse.ch/v1/url/', ['url' => $url])
                ->json();

            if (($data['query_status'] ?? null) === 'ok' && ! empty($data['threat'])) {
                return ['provider' => 'urlhaus', 'verdict' => 'malicious', 'raw' => $data];
            }

            return ['provider' => 'urlhaus', 'verdict' => 'clean', 'raw' => null];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function virusTotal(string $url): ?array
    {
        $key = config('linkforge.safety.providers.virustotal');
        if (! $key) {
            return null;
        }

        try {
            $id = rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
            $data = Http::timeout(5)
                ->withHeaders(['x-apikey' => $key])
                ->get("https://www.virustotal.com/api/v3/urls/{$id}")
                ->json();

            $stats = $data['data']['attributes']['last_analysis_stats'] ?? [];
            $malicious = (int) ($stats['malicious'] ?? 0);
            $suspicious = (int) ($stats['suspicious'] ?? 0);

            $verdict = $malicious > 0 ? 'malicious' : ($suspicious > 0 ? 'suspicious' : 'clean');

            return ['provider' => 'virustotal', 'verdict' => $verdict, 'raw' => $stats];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
