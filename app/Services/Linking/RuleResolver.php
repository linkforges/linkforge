<?php

namespace App\Services\Linking;

use App\Models\Link;
use Illuminate\Support\Carbon;

/**
 * Picks a link's destination based on visitor context. Targeting rules
 * (geo/device/os/language/time) are evaluated in order, first match wins.
 * If none match, weighted rotation rules split traffic; otherwise the base URL.
 *
 * @phpstan-type Ctx array{country:?string, device:?string, os:?string, language:?string, now:Carbon}
 */
class RuleResolver
{
    /** @param Ctx $ctx */
    public function resolve(Link $link, array $ctx): string
    {
        $rules = $link->relationLoaded('rules') ? $link->rules : $link->rules()->orderBy('sort')->get();

        if ($rules->isEmpty()) {
            return $link->long_url;
        }

        $rotation = [];
        foreach ($rules as $rule) {
            if ($rule->type === 'rotation') {
                $rotation[] = $rule;

                continue;
            }
            if ($this->matches($rule, $ctx)) {
                return $rule->target_url;
            }
        }

        if (! empty($rotation)) {
            return $this->weightedPick($rotation, $link->long_url);
        }

        return $link->long_url;
    }

    private function matches(object $rule, array $ctx): bool
    {
        $values = (array) (($rule->match_value['values'] ?? []) ?: []);

        return match ($rule->type) {
            'geo' => $ctx['country'] !== null && in_array($ctx['country'], $values, true),
            'device' => in_array($ctx['device'] ?? null, $values, true),
            'os' => in_array($ctx['os'] ?? null, $values, true),
            'language' => $this->languageMatches($ctx['language'] ?? null, $values),
            'time' => $this->timeMatches($ctx['now'] ?? now(), $rule->match_value['from'] ?? null, $rule->match_value['to'] ?? null),
            default => false,
        };
    }

    private function languageMatches(?string $language, array $values): bool
    {
        if (! $language) {
            return false;
        }
        $lang = strtolower(substr($language, 0, 2));
        foreach ($values as $value) {
            if (strtolower(substr((string) $value, 0, 2)) === $lang) {
                return true;
            }
        }

        return false;
    }

    private function timeMatches(Carbon $now, ?string $from, ?string $to): bool
    {
        if (! $from || ! $to) {
            return false;
        }
        $current = $now->format('H:i');

        return $from <= $to
            ? ($current >= $from && $current <= $to)
            : ($current >= $from || $current <= $to); // overnight window
    }

    /** @param array<int, object> $rules */
    private function weightedPick(array $rules, string $fallback): string
    {
        $total = 0;
        foreach ($rules as $rule) {
            $total += max(1, (int) ($rule->weight ?? 1));
        }
        if ($total <= 0) {
            return $fallback;
        }

        $pick = random_int(1, $total);
        $accumulated = 0;
        foreach ($rules as $rule) {
            $accumulated += max(1, (int) ($rule->weight ?? 1));
            if ($pick <= $accumulated) {
                return $rule->target_url;
            }
        }

        return $fallback;
    }
}
