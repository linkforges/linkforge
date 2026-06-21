<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $fillable = [
        'title', 'slug', 'body', 'status', 'show_in_footer', 'sort', 'meta_title', 'meta_description',
    ];

    protected $casts = ['show_in_footer' => 'boolean'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function getRenderedBodyAttribute(): string
    {
        return Str::markdown((string) $this->body);
    }
}
