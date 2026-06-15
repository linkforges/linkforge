<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'price', 'currency', 'interval', 'limits', 'features', 'is_active', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'limits' => 'array',
            'features' => 'array',
            'is_active' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** Read a plan limit (null = unlimited by convention). */
    public function limit(string $key, mixed $default = null): mixed
    {
        return data_get($this->limits, $key, $default);
    }

    /** Is a feature flag enabled on this plan? */
    public function allows(string $feature): bool
    {
        return (bool) data_get($this->features, $feature, false);
    }
}
