<?php

namespace App\Http\Controllers;

use App\Models\BioPage;
use App\Models\Link;
use App\Services\Analytics\GeoResolver;
use App\Services\Analytics\RecordClick;
use App\Services\Analytics\UaParser;
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
        $cfCountry = $request->headers->get('CF-IPCountry');
        $parsed = UaParser::parse($request->userAgent());
        $routeCtx = [
            'country' => app(GeoResolver::class)->country($request->ip(), $cfCountry),
            'device' => $parsed['device'],
            'os' => $parsed['os'],
            'language' => $request->getPreferredLanguage() ? substr((string) $request->getPreferredLanguage(), 0, 5) : null,
            'now' => now(),
        ];
        $target = $link->appendParams(app(RuleResolver::class)->resolve($link, $routeCtx));

        $ctx = [
            'link_id' => $link->id,
            'user_id' => $link->user_id,
            'alias' => $link->alias,
            'short_url' => $request->url(),
            'target' => $target,
            'ip' => $request->ip(),
            'cf_country' => $cfCountry,
            'cf_city' => $request->headers->get('CF-IPCity'),
            'cf_region' => $request->headers->get('CF-Region'),
            'ua' => $request->userAgent(),
            'referer' => $request->headers->get('referer'),
            'language' => substr((string) $request->getPreferredLanguage(), 0, 10) ?: null,
        ];
        app()->terminating(fn () => app(RecordClick::class)($ctx));

        // Render the interstitial splash for non-direct link types, OR whenever the link
        // has retargeting pixels attached. Pixels are client-side scripts and need an HTML
        // page to fire on, so even a "direct" link must pass through the splash to track.
        $pixels = $link->relationLoaded('pixels') ? $link->pixels : $link->pixels()->get();

        if ($link->type !== 'direct' || $pixels->isNotEmpty()) {
            return response()->view('redirect.splash', ['target' => $target, 'pixels' => $pixels]);
        }

        return redirect()->away($target, 302);
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

        $bio = app(\App\Services\Analytics\BioAnalytics::class);
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
            fn () => Link::with(['rules', 'pixels'])->where('domain_id', $domainId)->where('alias', $alias)->first()
        );
    }
}
