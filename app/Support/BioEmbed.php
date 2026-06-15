<?php

namespace App\Support;

/**
 * Turns a pasted URL into an embeddable iframe (src + sizing) for the platforms
 * that support clean URL-based embedding. Returns null for anything unsupported.
 */
class BioEmbed
{
    /** @return array{src:string, style:string}|null */
    public static function resolve(string $url): ?array
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // YouTube (video, shorts, or playlist)
        if (preg_match('~youtube\.com/playlist\?list=([\w-]+)~', $url, $m)) {
            return ['src' => 'https://www.youtube.com/embed/videoseries?list='.$m[1], 'style' => 'aspect-ratio:16/9'];
        }
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', $url, $m)) {
            if (preg_match('~[?&]list=([\w-]+)~', $url, $lm)) {
                return ['src' => 'https://www.youtube.com/embed/'.$m[1].'?list='.$lm[1], 'style' => 'aspect-ratio:16/9'];
            }

            return ['src' => 'https://www.youtube.com/embed/'.$m[1], 'style' => 'aspect-ratio:16/9'];
        }

        // Vimeo
        if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
            return ['src' => 'https://player.vimeo.com/video/'.$m[1], 'style' => 'aspect-ratio:16/9'];
        }

        // Spotify (track/episode are short; album/playlist/artist are tall)
        if (preg_match('~open\.spotify\.com/(track|album|playlist|artist|episode|show)/([A-Za-z0-9]+)~', $url, $m)) {
            $height = in_array($m[1], ['track', 'episode'], true) ? 152 : 352;

            return ['src' => "https://open.spotify.com/embed/{$m[1]}/{$m[2]}", 'style' => "height:{$height}px"];
        }

        // Apple Music
        if (preg_match('~music\.apple\.com/(.+)$~', $url, $m)) {
            return ['src' => 'https://embed.music.apple.com/'.$m[1], 'style' => 'height:450px'];
        }

        // SoundCloud
        if (str_contains($url, 'soundcloud.com/')) {
            return ['src' => 'https://w.soundcloud.com/player/?url='.urlencode($url).'&color=%23ff5500&show_comments=false', 'style' => 'height:166px'];
        }

        // Calendly
        if (str_contains($url, 'calendly.com/')) {
            return ['src' => $url, 'style' => 'height:660px'];
        }

        // Typeform
        if (preg_match('~typeform\.com/to/([\w]+)~', $url, $m)) {
            return ['src' => 'https://form.typeform.com/to/'.$m[1], 'style' => 'height:520px'];
        }

        return null;
    }
}
