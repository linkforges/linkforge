<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbuseReport extends Model
{
    protected $fillable = ['link_id', 'reporter_email', 'reason', 'status'];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
