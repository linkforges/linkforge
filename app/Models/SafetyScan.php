<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyScan extends Model
{
    protected $table = 'safety_scans';

    public $timestamps = false;

    protected $fillable = ['link_id', 'provider', 'verdict', 'score', 'raw', 'scanned_at'];

    protected function casts(): array
    {
        return [
            'raw' => 'array',
            'scanned_at' => 'datetime',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
