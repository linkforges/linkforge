<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkRule extends Model
{
    protected $fillable = [
        'link_id', 'type', 'match_value', 'target_url', 'weight', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'match_value' => 'array',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
