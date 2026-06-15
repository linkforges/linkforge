<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrCode extends Model
{
    protected $fillable = [
        'user_id', 'link_id', 'name', 'type', 'is_dynamic',
        'content', 'data', 'design', 'format', 'file_path', 'scans',
    ];

    protected function casts(): array
    {
        return [
            'design' => 'array',
            'data' => 'array',
            'is_dynamic' => 'boolean',
            'scans' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
