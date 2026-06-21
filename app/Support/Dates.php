<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Formats a date using the operator's chosen display format (Settings -> General),
 * in the operator's timezone. Use \App\Support\Dates::format($date) or the @lfdate
 * Blade directive in views.
 */
class Dates
{
    public static function format(mixed $date): string
    {
        if (empty($date)) {
            return '';
        }

        return Carbon::parse($date)->format((string) config('linkforge.date_format', 'M j, Y'));
    }
}
