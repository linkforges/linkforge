<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    private const CACHE_KEY = 'linkforge.settings';

    /** Fetch a setting value (with default). */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::allCached()[$key] ?? $default;
    }

    /** Create or update a setting. */
    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        static::flushCache();
    }

    /** Bulk upsert; flushes the cache once. */
    public static function putMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
        static::flushCache();
    }

    /** All settings as key => value, cached. Resilient to a missing table (install / pre-migrate). */
    public static function allCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                return static::query()->pluck('value', 'key')->all();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
