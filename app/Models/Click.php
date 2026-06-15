<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Click extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'link_id', 'ip_hash', 'country', 'region', 'city', 'device', 'os', 'browser',
        'referer_host', 'language', 'is_bot', 'utm', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'utm' => 'array',
            'is_bot' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
