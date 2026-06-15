<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BioPage extends Model
{
    protected $fillable = [
        'user_id', 'slug', 'title', 'theme', 'settings', 'social_links', 'is_published', 'views',
    ];

    protected function casts(): array
    {
        return [
            'theme' => 'array',
            'settings' => 'array',
            'social_links' => 'array',
            'is_published' => 'boolean',
        ];
    }

    /** Setting accessor with a default. */
    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(BioBlock::class)->orderBy('sort');
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(BioSubscriber::class)->latest();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(BioMessage::class)->latest();
    }
}
