<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ScanLink;
use App\Models\Link;
use App\Services\Billing\PlanGate;
use App\Services\Linking\AliasGenerator;
use App\Services\Linking\DomainResolver;
use App\Services\Safety\LinkSafety;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $links = $request->user()->links()->with('domain')->latest()
            ->paginate(min(100, max(1, (int) $request->query('per_page', 25))));

        return response()->json([
            'data' => collect($links->items())->map(fn (Link $l) => $this->resource($l)),
            'meta' => [
                'current_page' => $links->currentPage(),
                'last_page' => $links->lastPage(),
                'per_page' => $links->perPage(),
                'total' => $links->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! app(PlanGate::class)->canCreate($user, 'max_links')) {
            return response()->json(['message' => "You've reached your plan's link limit."], 422);
        }

        $data = $request->validate([
            'long_url' => ['required', 'url', 'max:2048'],
            'alias' => ['nullable', 'string', 'max:190'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        if ($error = app(LinkSafety::class)->screen($data['long_url'])) {
            return response()->json(['message' => $error, 'errors' => ['long_url' => [$error]]], 422);
        }

        $domain = app(DomainResolver::class)->default();
        $aliases = app(AliasGenerator::class);
        $alias = trim((string) ($data['alias'] ?? ''));

        if ($alias !== '') {
            if ($error = $aliases->validateCustom($alias, $domain->id)) {
                return response()->json(['message' => $error, 'errors' => ['alias' => [$error]]], 422);
            }
        } else {
            $alias = $aliases->generate($domain->id);
        }

        $link = $user->links()->create([
            'domain_id' => $domain->id,
            'alias' => $alias,
            'long_url' => $data['long_url'],
            'title' => $data['title'] ?? null,
            'type' => 'direct',
            'safety_status' => 'pending',
        ]);
        $link->setRelation('domain', $domain);
        ScanLink::dispatchSync($link->id);
        Link::forgetCache($domain->id, $alias);

        return response()->json(['data' => $this->resource($link->fresh()->load('domain'))], 201);
    }

    public function show(Request $request, Link $link): JsonResponse
    {
        $this->authorizeLink($request, $link);

        return response()->json(['data' => $this->resource($link->load('domain'))]);
    }

    public function update(Request $request, Link $link): JsonResponse
    {
        $this->authorizeLink($request, $link);

        $data = $request->validate([
            'long_url' => ['sometimes', 'url', 'max:2048'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $link->update($data);
        Link::forgetCache($link->domain_id, $link->alias);

        return response()->json(['data' => $this->resource($link->fresh()->load('domain'))]);
    }

    public function destroy(Request $request, Link $link): JsonResponse
    {
        $this->authorizeLink($request, $link);

        $domainId = $link->domain_id;
        $alias = $link->alias;
        $link->delete();
        Link::forgetCache($domainId, $alias);

        return response()->json(['message' => 'Link deleted.']);
    }

    private function authorizeLink(Request $request, Link $link): void
    {
        abort_unless((int) $link->user_id === (int) $request->user()->id, 403);
    }

    /** @return array<string, mixed> */
    private function resource(Link $link): array
    {
        return [
            'id' => $link->id,
            'alias' => $link->alias,
            'short_url' => request()->getScheme().'://'.$link->shortUrl(),
            'destination' => $link->long_url,
            'title' => $link->title,
            'type' => $link->type,
            'clicks' => (int) $link->clicks,
            'is_active' => (bool) $link->is_active,
            'safety_status' => $link->safety_status,
            'created_at' => $link->created_at?->toIso8601String(),
        ];
    }
}
