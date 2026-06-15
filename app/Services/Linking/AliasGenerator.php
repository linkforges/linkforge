<?php

namespace App\Services\Linking;

use App\Models\Link;
use App\Models\Setting;

class AliasGenerator
{
    /** Base62 minus visually ambiguous characters (0/O, 1/l/I). */
    private const ALPHABET = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /** Generate a unique random alias for the given domain. */
    public function generate(int $domainId, int $length = 6): string
    {
        do {
            $alias = $this->random($length);
        } while ($this->isReserved($alias) || $this->taken($domainId, $alias));

        return $alias;
    }

    public function random(int $length = 6): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)];
        }

        return $out;
    }

    public function taken(int $domainId, string $alias): bool
    {
        return Link::where('domain_id', $domainId)->where('alias', $alias)->exists();
    }

    public function isReserved(string $alias): bool
    {
        return in_array(strtolower($alias), $this->reserved(), true);
    }

    /** @return list<string> */
    public function reserved(): array
    {
        $raw = Setting::get('reserved_aliases');
        $list = $raw ? json_decode($raw, true) : [];

        return is_array($list) ? array_map('strtolower', $list) : [];
    }

    /**
     * Validate a user-supplied custom alias.
     *
     * @return string|null  Error message, or null if valid.
     */
    public function validateCustom(string $alias, int $domainId, ?int $ignoreLinkId = null): ?string
    {
        if (! preg_match('/^[A-Za-z0-9\-_]{1,190}$/', $alias)) {
            return 'Use only letters, numbers, hyphens and underscores.';
        }

        if ($this->isReserved($alias)) {
            return 'That alias is reserved. Please choose another.';
        }

        $exists = Link::where('domain_id', $domainId)
            ->where('alias', $alias)
            ->when($ignoreLinkId, fn ($q) => $q->where('id', '!=', $ignoreLinkId))
            ->exists();

        if ($exists) {
            return 'That alias is already taken.';
        }

        return null;
    }
}
