<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatDaily extends Model
{
    protected $table = 'stat_daily';

    public $timestamps = false;

    protected $fillable = ['link_id', 'day', 'clicks', 'uniques', 'bots'];

    protected function casts(): array
    {
        return ['day' => 'date'];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
