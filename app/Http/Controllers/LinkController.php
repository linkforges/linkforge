<?php

namespace App\Http\Controllers;

use App\Jobs\ScanLink;
use App\Models\Link;
use App\Models\User;
use App\Models\Webhook;
use App\Services\Ai\ClaudeClient;
use App\Services\Billing\PlanGate;
use App\Services\Linking\AliasGenerator;
use App\Services\Linking\DomainResolver;
use App\Services\Safety\LinkSafety;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LinkController extends Controller
{
    public function __construct(
        private AliasGenerator $aliases,
        private DomainResolver $domains,
    ) {}

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $links = $request->user()->links()
            ->with('domain')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('alias', 'like', "%{$q}%")
                        ->orWhere('long_url', 'like', "%{$q}%")
                        ->orWhere('title', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('links.index', [
            'links' => $links,
            'q' => $q,
            'domain' => $this->domains->default(),
        ]);
    }

    public function create(Request $request)
    {
        $domain = $this->domains->default();

        return view('links.create', [
            'domain' => $domain,
            'suggestion' => $domain ? $this->aliases->generate($domain->id) : '',
            'pixels' => $request->user()->pixels()->get(),
            'attachedPixelIds' => [],
            'aiEnabled' => app(ClaudeClient::class)->enabled(),
        ]);
    }

    public function store(Request $request)
    {
        $domain = $this->domains->default();
        abort_unless($domain, 500, 'No default domain configured.');

        $user = $request->user();
        if (! app(PlanGate::class)->canCreate($user, 'max_links')) {
            return back()->withInput()->with('error', "You've reached your plan's link limit. Upgrade to create more.");
        }

        $data = $this->validateLink($request);

        $alias = trim((string) ($data['alias'] ?? ''));
        if ($alias !== '') {
            if ($error = $this->aliases->validateCustom($alias, $domain->id)) {
                return back()->withInput()->withErrors(['alias' => $error]);
            }
        } else {
            $alias = $this->aliases->generate($domain->id);
        }

        $link = $user->links()->create([
            'domain_id' => $domain->id,
            'alias' => $alias,
            'long_url' => $data['long_url'],
            'params' => $this->buildParams($data),
            'title' => $data['title'] ?? null,
            'type' => $data['type'] ?? 'direct',
            'password' => ! empty($data['password']) ? Hash::make($data['password']) : null,
            'expires_at' => $data['expires_at'] ?? null,
            'click_limit' => $data['click_limit'] ?? null,
            'safety_status' => 'pending',
        ]);

        $link->setRelation('domain', $domain);
        $this->syncRules($request, $link);
        $this->syncPixels($request, $link);
        ScanLink::dispatchSync($link->id);
        $this->fireWebhooks($user, 'link.created', [
            'id' => $link->id,
            'alias' => $link->alias,
            'short_url' => $link->shortUrl(),
            'long_url' => $link->long_url,
        ]);
        Link::forgetCache($domain->id, $alias);

        return redirect()->route('links.index')->with('status', 'Link created: '.$link->shortUrl());
    }

    public function edit(Request $request, Link $link)
    {
        abort_unless($link->user_id === $request->user()->id, 403);
        $link->load('domain');

        return view('links.edit', [
            'link' => $link,
            'domain' => $link->domain,
            'pixels' => $request->user()->pixels()->get(),
            'attachedPixelIds' => $link->pixels()->pluck('pixels.id')->all(),
            'aiEnabled' => app(ClaudeClient::class)->enabled(),
        ]);
    }

    public function update(Request $request, Link $link)
    {
        abort_unless($link->user_id === $request->user()->id, 403);

        $domain = $link->domain ?: $this->domains->default();
        $data = $this->validateLink($request);

        $oldAlias = $link->alias;
        $alias = trim((string) ($data['alias'] ?? ''));
        if ($alias !== '' && $alias !== $oldAlias) {
            if ($error = $this->aliases->validateCustom($alias, $domain->id, $link->id)) {
                return back()->withInput()->withErrors(['alias' => $error]);
            }
        } else {
            $alias = $oldAlias;
        }

        $link->fill([
            'alias' => $alias,
            'long_url' => $data['long_url'],
            'params' => $this->buildParams($data),
            'title' => $data['title'] ?? null,
            'type' => $data['type'] ?? 'direct',
            'expires_at' => $data['expires_at'] ?? null,
            'click_limit' => $data['click_limit'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($data['password'])) {
            $link->password = Hash::make($data['password']);
        }

        $link->save();
        $this->syncRules($request, $link);
        $this->syncPixels($request, $link);
        ScanLink::dispatchSync($link->id);

        Link::forgetCache($domain->id, $oldAlias);
        Link::forgetCache($domain->id, $alias);

        return redirect()->route('links.index')->with('status', 'Link updated.');
    }

    public function destroy(Request $request, Link $link)
    {
        abort_unless($link->user_id === $request->user()->id, 403);

        $domainId = $link->domain_id;
        $alias = $link->alias;
        $link->delete();

        Link::forgetCache($domainId, $alias);

        return redirect()->route('links.index')->with('status', 'Link deleted.');
    }

    /** @return array<string, mixed> */
    private function validateLink(Request $request): array
    {
        $data = $request->validate([
            'long_url' => ['required', 'url', 'max:2048'],
            'alias' => ['nullable', 'string', 'max:190'],
            'title' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:direct,frame,splash,overlay,cta'],
            'expires_at' => ['nullable', 'date'],
            'password' => ['nullable', 'string', 'max:100'],
            'click_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'utm_source' => ['nullable', 'string', 'max:150'],
            'utm_medium' => ['nullable', 'string', 'max:150'],
            'utm_campaign' => ['nullable', 'string', 'max:150'],
            'utm_term' => ['nullable', 'string', 'max:150'],
            'utm_content' => ['nullable', 'string', 'max:150'],
            'custom_params' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($error = app(LinkSafety::class)->screen($data['long_url'])) {
            throw ValidationException::withMessages(['long_url' => $error]);
        }

        return $data;
    }

    /**
     * Assemble the UTM + custom query parameters from the form into a flat map,
     * or null when none are set. Custom params are "key=value" per line.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>|null
     */
    private function buildParams(array $data): ?array
    {
        $params = [];
        foreach (['source', 'medium', 'campaign', 'term', 'content'] as $k) {
            $v = trim((string) ($data["utm_{$k}"] ?? ''));
            if ($v !== '') {
                $params["utm_{$k}"] = $v;
            }
        }
        foreach (preg_split('/\r\n|\r|\n/', (string) ($data['custom_params'] ?? '')) as $line) {
            $line = trim($line);
            if ($line === '' || ! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if ($key !== '') {
                $params[$key] = $value;
            }
        }

        return $params ?: null;
    }

    /** Replace a link's targeting / rotation rules from the submitted form. */
    private function syncRules(Request $request, Link $link): void
    {
        $link->rules()->delete();

        $rows = $request->input('rules', []);
        if (! is_array($rows)) {
            return;
        }

        $sort = 0;
        foreach ($rows as $row) {
            $type = $row['type'] ?? null;
            $target = trim((string) ($row['target_url'] ?? ''));

            if (! in_array($type, ['geo', 'device', 'os', 'language', 'time', 'rotation'], true)) {
                continue;
            }
            if ($target === '' || ! filter_var($target, FILTER_VALIDATE_URL)) {
                continue;
            }

            $link->rules()->create([
                'type' => $type,
                'match_value' => $this->parseRuleMatch($type, (string) ($row['match'] ?? '')),
                'target_url' => $target,
                'weight' => $type === 'rotation' ? max(1, (int) ($row['weight'] ?? 1)) : null,
                'sort' => $sort++,
            ]);
        }
    }

    /** Sync the visitor-retargeting pixels attached to a link (owner's pixels only). */
    private function syncPixels(Request $request, Link $link): void
    {
        $ids = array_map('intval', (array) $request->input('pixels', []));
        $valid = $request->user()->pixels()->whereIn('id', $ids)->pluck('id')->all();
        $link->pixels()->sync($valid);
    }

    /** Queue any of the user's active webhooks that subscribe to the given event. */
    private function fireWebhooks(User $user, string $event, array $payload): void
    {
        Webhook::fire($user->id, $event, $payload);
    }

    /** @return array<string, mixed>|null */
    private function parseRuleMatch(string $type, string $match): ?array
    {
        $match = trim($match);

        if ($type === 'rotation') {
            return null;
        }

        if ($type === 'time') {
            [$from, $to] = array_pad(array_map('trim', explode('-', $match, 2)), 2, null);

            return ['from' => $from ?: null, 'to' => $to ?: null];
        }

        $values = array_values(array_filter(array_map('trim', explode(',', $match))));
        if ($type === 'geo') {
            $values = array_map('strtoupper', $values);
        }

        return ['values' => $values];
    }
}
