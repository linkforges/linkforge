<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatDimension extends Model
{
    protected $table = 'stat_dimension';

    public $timestamps = false;

    protected $fillable = ['link_id', 'day', 'dimension', 'label', 'clicks'];

    protected function casts(): array
    {
        return ['day' => 'date'];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
