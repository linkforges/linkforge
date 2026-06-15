<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrTemplate extends Model
{
    protected $fillable = ['user_id', 'name', 'design'];

    protected function casts(): array
    {
        return ['design' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
