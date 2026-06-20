<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HelpArticle extends Model
{
    protected $fillable = [
        'category', 'title', 'slug', 'excerpt', 'body',
        'status', 'sort', 'views', 'meta_title', 'meta_description',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function getRenderedBodyAttribute(): string
    {
        return Str::markdown((string) $this->body);
    }
}
