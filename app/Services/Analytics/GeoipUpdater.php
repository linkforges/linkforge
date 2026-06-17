<?php

namespace App\Services\Analytics;

use App\Models\Setting;
use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Downloads / refreshes the GeoIP database the app uses for country + city
 * analytics, so operators never have to find, decompress, and upload a database
 * by hand.
 *
 *  - DB-IP Lite (default): a gzipped .mmdb at a predictable monthly URL, no
 *    account required, CC-BY (attribution shown in the admin).
 *  - MaxMind GeoLite2 (optional): a .tar.gz fetched with the operator's free
 *    license key, for those who prefer it.
 *
 * The result is written to storage/app/geoip/geoip.mmdb, which the resolver
 * prefers over the bundled seed.
 *
 * The large City database (~60 MB compressed) is fetched in small HTTP Range
 * chunks (see beginChunkedDownload/pullChunk/finishChunkedDownload) so each web
 * request stays well under shared-hosting proxy / FastCGI timeouts — a plain
 * one-shot download of that size reliably 504s on cheap hosts. The CLI path
 * (geoip:update, used by the monthly scheduler) still streams it in one go.
 */
class GeoipUpdater
{
    public const EDITIONS = [
        'country' => 'Country only (small, ~8 MB)',
        'city' => 'Country + City (large, ~120 MB)',
    ];

    public const PROVIDERS = [
        'dbip' => 'DB-IP Lite (free, no account)',
        'maxmind' => 'MaxMind GeoLite2 (free license key)',
    ];

    /** Bytes pulled per chunked request. Small enough to never hit a request timeout. */
    public const CHUNK_BYTES = 8 * 1024 * 1024;

    public function targetPath(): string
    {
        return storage_path('app/geoip/geoip.mmdb');
    }

    /**
     * Run an update for the given (or configured) provider + edition in one shot
     * (used by the CLI command / scheduler). For large files over the web, prefer
     * the chunked flow below.
     *
     * @return string human-readable success message
     */
    public function update(?string $provider = null, ?string $edition = null): string
    {
        [$provider, $edition] = $this->resolve($provider, $edition);

        $dir = $this->ensureDir();
        $base = $dir.DIRECTORY_SEPARATOR.'.download-'.bin2hex(random_bytes(4));

        try {
            $mmdb = $provider === 'maxmind'
                ? $this->fetchMaxmind($edition, $base)
                : $this->fetchDbip($edition, $base);

            return $this->installMmdb($mmdb, $provider, $edition);
        } finally {
            foreach (glob($dir.DIRECTORY_SEPARATOR.'.download-*') ?: [] as $f) {
                @unlink($f);
            }
        }
    }

    // ---- Chunked / resumable download (web UI) -----------------------------

    /**
     * Begin a chunked download. Returns ['chunked' => true, 'total' => bytes,
     * 'received' => bytes] when the source supports HTTP Range, or
     * ['chunked' => false] to tell the caller to fall back to a one-shot update()
     * (MaxMind, or a server that ignores Range).
     */
    public function beginChunkedDownload(?string $provider = null, ?string $edition = null): array
    {
        [$provider, $edition] = $this->resolve($provider, $edition);

        if ($provider !== 'dbip') {
            return ['chunked' => false]; // MaxMind uses a signed/redirected URL: one-shot.
        }

        $this->ensureDir();
        $url = $this->dbipUrl($edition);

        $head = Http::timeout(30)->head($url);
        $total = (int) $head->header('Content-Length');
        $ranges = strtolower((string) $head->header('Accept-Ranges'));
        if ($total < 1 || ! str_contains($ranges, 'bytes')) {
            return ['chunked' => false];
        }

        // Resume a matching partial download if one exists; otherwise start fresh.
        $prev = $this->peekState();
        $resume = $prev && ($prev['url'] ?? null) === $url
            && is_file($this->partPath()) && filesize($this->partPath()) < $total;
        if (! $resume) {
            file_put_contents($this->partPath(), '');
        }

        file_put_contents($this->statePath(), json_encode([
            'url' => $url, 'total' => $total, 'provider' => $provider, 'edition' => $edition,
        ]));

        return [
            'chunked' => true,
            'total' => $total,
            'received' => is_file($this->partPath()) ? (int) filesize($this->partPath()) : 0,
        ];
    }

    /**
     * Download the next chunk onto the partial file.
     *
     * @return array{received:int,total:int,done:bool}
     */
    public function pullChunk(): array
    {
        $state = $this->readState();
        $part = $this->partPath();
        $total = (int) $state['total'];
        $received = is_file($part) ? (int) filesize($part) : 0;

        if ($received >= $total) {
            return ['received' => $received, 'total' => $total, 'done' => true];
        }

        $end = min($received + self::CHUNK_BYTES, $total) - 1;
        $res = Http::timeout(180)->withHeaders(['Range' => "bytes={$received}-{$end}"])->get($state['url']);
        if (! $res->successful()) {
            throw new RuntimeException('Download interrupted (HTTP '.$res->status().'). Click download to resume.');
        }
        $body = $res->body();
        if ($body === '') {
            throw new RuntimeException('Download stalled with no data. Click download to resume.');
        }
        file_put_contents($part, $body, FILE_APPEND);

        $received = (int) filesize($part);

        return ['received' => $received, 'total' => $total, 'done' => $received >= $total];
    }

    /** Decompress, validate and install the fully-downloaded partial file. */
    public function finishChunkedDownload(): string
    {
        $state = $this->readState();
        $part = $this->partPath();
        if (! is_file($part) || filesize($part) < (int) $state['total']) {
            throw new RuntimeException('Download is incomplete. Click download to resume.');
        }

        $mmdb = dirname($this->targetPath()).DIRECTORY_SEPARATOR.'.chunk-data.mmdb';
        try {
            $this->gunzip($part, $mmdb); // removes $part on success
            $message = $this->installMmdb($mmdb, $state['provider'], $state['edition']);
        } finally {
            @unlink($part);
            @unlink($mmdb);
            @unlink($this->statePath());
        }

        return $message;
    }

    // ---- internals ---------------------------------------------------------

    /** Validate, atomically install, prune to one DB, and record the result. */
    private function installMmdb(string $mmdb, string $provider, string $edition): string
    {
        new Reader($mmdb); // throws if it is not a valid database

        $dest = $this->targetPath();
        if (! @rename($mmdb, $dest)) {
            copy($mmdb, $dest);
            @unlink($mmdb);
        }

        // Keep exactly one active .mmdb so the resolver is unambiguous.
        // (Compare by basename so it is robust to path-separator differences.)
        foreach (glob(dirname($dest).DIRECTORY_SEPARATOR.'*.mmdb') ?: [] as $f) {
            if (basename($f) !== basename($dest)) {
                @unlink($f);
            }
        }

        Setting::putMany([
            'geoip_provider' => $provider,
            'geoip_edition' => $edition,
            'geoip_updated_at' => now()->toIso8601String(),
            'geoip_source' => ($provider === 'maxmind' ? 'MaxMind GeoLite2' : 'DB-IP Lite').' · '.ucfirst($edition),
        ]);

        return ($edition === 'city' ? 'City' : 'Country').' database installed from '
            .($provider === 'maxmind' ? 'MaxMind' : 'DB-IP').'.';
    }

    /** @return array{0:string,1:string} normalized [provider, edition] */
    private function resolve(?string $provider, ?string $edition): array
    {
        $provider = array_key_exists($provider ?? '', self::PROVIDERS) ? $provider : (string) Setting::get('geoip_provider', 'dbip');
        $provider = array_key_exists($provider, self::PROVIDERS) ? $provider : 'dbip';
        $edition = array_key_exists($edition ?? '', self::EDITIONS) ? $edition : (string) Setting::get('geoip_edition', 'country');
        $edition = array_key_exists($edition, self::EDITIONS) ? $edition : 'country';

        return [$provider, $edition];
    }

    private function ensureDir(): string
    {
        $dir = dirname($this->targetPath());
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private function statePath(): string
    {
        return dirname($this->targetPath()).DIRECTORY_SEPARATOR.'.chunk-state.json';
    }

    private function partPath(): string
    {
        return dirname($this->targetPath()).DIRECTORY_SEPARATOR.'.chunk-data.part';
    }

    private function peekState(): ?array
    {
        $raw = @file_get_contents($this->statePath());
        $state = $raw ? json_decode($raw, true) : null;

        return is_array($state) ? $state : null;
    }

    private function readState(): array
    {
        $state = $this->peekState();
        if (! $state || empty($state['url'])) {
            throw new RuntimeException('No download in progress. Click download to start.');
        }

        return $state;
    }

    /** DB-IP Lite: the gzipped .mmdb URL for the current month, else the previous one. */
    private function dbipUrl(string $edition): string
    {
        foreach ($this->recentMonths(2) as $ym) {
            $url = "https://download.db-ip.com/free/dbip-{$edition}-lite-{$ym}.mmdb.gz";
            try {
                if (Http::timeout(30)->head($url)->successful()) {
                    return $url;
                }
            } catch (\Throwable $e) {
                // try the previous month
            }
        }

        throw new RuntimeException('Could not reach the DB-IP database server. Make sure the server can reach download.db-ip.com.');
    }

    /** DB-IP Lite one-shot fetch (CLI path): download + decompress in one request. */
    private function fetchDbip(string $edition, string $base): string
    {
        $gz = $base.'.gz';
        if (! $this->download($this->dbipUrl($edition), $gz)) {
            throw new RuntimeException('Could not download the DB-IP database. Make sure the server can reach download.db-ip.com.');
        }

        $mmdb = $base.'.mmdb';
        $this->gunzip($gz, $mmdb);

        return $mmdb;
    }

    /** MaxMind GeoLite2: a .tar.gz fetched with the license key; the .mmdb is inside. */
    private function fetchMaxmind(string $edition, string $base): string
    {
        $key = trim((string) Setting::get('geoip_maxmind_key'));
        if ($key === '') {
            throw new RuntimeException('Enter your MaxMind license key first (free from maxmind.com).');
        }

        $editionId = $edition === 'city' ? 'GeoLite2-City' : 'GeoLite2-Country';
        $url = "https://download.maxmind.com/app/geoip_download?edition_id={$editionId}&license_key={$key}&suffix=tar.gz";

        $tarGz = $base.'.tar.gz';
        if (! $this->download($url, $tarGz)) {
            throw new RuntimeException('MaxMind download failed. Check your license key and that the server can reach maxmind.com.');
        }

        return $this->extractMmdbFromTarGz($tarGz, $base);
    }

    /** @return list<string> e.g. ['2026-06', '2026-05'] */
    private function recentMonths(int $count): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = now()->copy()->subMonthsNoOverflow($i)->format('Y-m');
        }

        return $out;
    }

    /** Stream a URL to a file. Returns false on any failure (caller decides). */
    private function download(string $url, string $dest): bool
    {
        try {
            $res = Http::timeout(600)->withOptions(['sink' => $dest])->get($url);
            if (! $res->successful() || ! is_file($dest) || filesize($dest) < 1000) {
                @unlink($dest);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            @unlink($dest);

            return false;
        }
    }

    /** Streamed gunzip (chunked) so large databases never load fully into memory. */
    private function gunzip(string $src, string $dest): void
    {
        $in = @gzopen($src, 'rb');
        $out = @fopen($dest, 'wb');
        if (! $in || ! $out) {
            throw new RuntimeException('Could not decompress the downloaded database.');
        }
        while (! gzeof($in)) {
            fwrite($out, gzread($in, 262144));
        }
        gzclose($in);
        fclose($out);
        @unlink($src);
    }

    /** Pull the single .mmdb member out of a MaxMind .tar.gz. */
    private function extractMmdbFromTarGz(string $tarGz, string $base): string
    {
        $tar = $base.'.tar';
        $this->gunzip($tarGz, $tar);

        try {
            $phar = new \PharData($tar);
            foreach (new \RecursiveIteratorIterator($phar) as $file) {
                if (str_ends_with((string) $file->getFilename(), '.mmdb')) {
                    $mmdb = $base.'.mmdb';
                    copy($file->getPathname(), $mmdb);
                    @unlink($tar);

                    return $mmdb;
                }
            }
        } catch (\Throwable $e) {
            @unlink($tar);
            throw new RuntimeException('Could not read the MaxMind archive: '.$e->getMessage());
        }

        @unlink($tar);
        throw new RuntimeException('No .mmdb file was found inside the MaxMind archive.');
    }
}
