<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BioSubscriber extends Model
{
    protected $fillable = ['bio_page_id', 'email', 'name', 'ip_hash'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(BioPage::class, 'bio_page_id');
    }
}
