<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BioBlock extends Model
{
    public $timestamps = false;

    protected $fillable = ['bio_page_id', 'type', 'content', 'sort', 'is_active'];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(BioPage::class, 'bio_page_id');
    }
}
