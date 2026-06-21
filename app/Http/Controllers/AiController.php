<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Services\Ai\AiCredits;
use App\Services\Ai\AliasSuggester;
use App\Services\Ai\BioCopywriter;
use App\Services\Ai\ClaudeClient;
use App\Services\Ai\LinkInsight;
use App\Services\Ai\NlAnalytics;
use App\Services\Ai\TitleWriter;
use App\Services\Linking\DomainResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON endpoints behind the in-app AI features (alias suggestions, "ask your
 * links"). Each call is gated on the AI layer being configured and the user
 * having credits, charges atomically, and refunds on failure.
 */
class AiController extends Controller
{
    public function __construct(
        private ClaudeClient $claude,
        private AiCredits $credits,
    ) {}

    public function suggestAlias(Request $request, AliasSuggester $suggester, DomainResolver $domains): JsonResponse
    {
        $data = $request->validate([
            'long_url' => ['required', 'url', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $cost = (int) config('linkforge.ai.cost.alias', 1);
        if ($error = $this->guard($request, $cost)) {
            return $error;
        }

        $domain = $domains->default();
        abort_unless($domain, 500, 'No default domain configured.');

        $user = $request->user();
        $this->credits->charge($user, $cost);

        try {
            $suggestions = $suggester->suggest($data['long_url'], $data['title'] ?? null, $domain->id);
        } catch (\Throwable $e) {
            $this->credits->refund($user, $cost);

            return response()->json(['message' => 'The AI service is unavailable right now. Please try again.'], 502);
        }

        return response()->json([
            'suggestions' => $suggestions,
            'credits' => $this->credits->balance($user),
        ]);
    }

    public function ask(Request $request, NlAnalytics $nl): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:300'],
        ]);

        $cost = (int) config('linkforge.ai.cost.ask', 1);
        if ($error = $this->guard($request, $cost)) {
            return $error;
        }

        $user = $request->user();
        $this->credits->charge($user, $cost);

        try {
            $result = $nl->answer($user, $data['question']);
        } catch (\Throwable $e) {
            $this->credits->refund($user, $cost);

            return response()->json(['message' => 'The AI service is unavailable right now. Please try again.'], 502);
        }

        // A question we couldn't map isn't a billable answer.
        if (! ($result['understood'] ?? false)) {
            $this->credits->refund($user, $cost);
        }

        return response()->json($result + ['credits' => $this->credits->balance($user)]);
    }

    /** Generate a title + description for a link's destination URL. */
    public function writeTitle(Request $request, TitleWriter $writer): JsonResponse
    {
        $data = $request->validate([
            'long_url' => ['required', 'url', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $cost = (int) config('linkforge.ai.cost.alias', 1);
        if ($error = $this->guard($request, $cost)) {
            return $error;
        }

        $user = $request->user();
        $this->credits->charge($user, $cost);

        try {
            $result = $writer->write($data['long_url'], $data['title'] ?? null);
        } catch (\Throwable $e) {
            $this->credits->refund($user, $cost);

            return response()->json(['message' => 'The AI service is unavailable right now. Please try again.'], 502);
        }

        return response()->json($result + ['credits' => $this->credits->balance($user)]);
    }

    /** Summarise one link's recent performance (the link must belong to the user). */
    public function linkInsight(Request $request, Link $link, LinkInsight $insight): JsonResponse
    {
        abort_unless((int) $link->user_id === (int) $request->user()->id, 403);

        $cost = (int) config('linkforge.ai.cost.ask', 1);
        if ($error = $this->guard($request, $cost)) {
            return $error;
        }

        $user = $request->user();
        $this->credits->charge($user, $cost);

        try {
            $summary = $insight->write($link);
        } catch (\Throwable $e) {
            $this->credits->refund($user, $cost);

            return response()->json(['message' => 'The AI service is unavailable right now. Please try again.'], 502);
        }

        return response()->json(['summary' => $summary, 'credits' => $this->credits->balance($user)]);
    }

    /** Generate link-in-bio copy (display name, headline, bio) from a topic. */
    public function bioCopy(Request $request, BioCopywriter $writer): JsonResponse
    {
        $data = $request->validate([
            'topic' => ['required', 'string', 'max:200'],
        ]);

        $cost = (int) config('linkforge.ai.cost.alias', 1);
        if ($error = $this->guard($request, $cost)) {
            return $error;
        }

        $user = $request->user();
        $this->credits->charge($user, $cost);

        try {
            $result = $writer->write($data['topic']);
        } catch (\Throwable $e) {
            $this->credits->refund($user, $cost);

            return response()->json(['message' => 'The AI service is unavailable right now. Please try again.'], 502);
        }

        return response()->json($result + ['credits' => $this->credits->balance($user)]);
    }

    /** Shared gate: AI enabled + sufficient credits. Returns a JSON error or null. */
    private function guard(Request $request, int $cost): ?JsonResponse
    {
        if (! $this->claude->enabled()) {
            return response()->json(['message' => 'AI features are not enabled on this site.'], 503);
        }

        if (! $this->credits->has($request->user(), $cost)) {
            return response()->json(['message' => "You're out of AI credits. They renew when your plan renews."], 402);
        }

        return null;
    }
}
