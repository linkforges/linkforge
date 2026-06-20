<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Post extends Model
{
    protected $fillable = [
        'author_id', 'title', 'slug', 'excerpt', 'body', 'cover_image',
        'status', 'published_at', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Published posts whose publish time has arrived (or is unset). */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(fn ($w) => $w->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function getRenderedBodyAttribute(): string
    {
        return Str::markdown((string) $this->body);
    }

    public function getReadingTimeAttribute(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->body)) / 200));
    }
}
