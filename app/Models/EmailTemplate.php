<?php

namespace App\Models;

use App\Support\EmailEvents;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['event', 'subject', 'body', 'enabled'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    /**
     * The effective template for an event: the stored override merged over the
     * registry default. Returns null for an unknown event.
     *
     * @return array{event:string,label:string,subject:string,body:string,enabled:bool}|null
     */
    public static function resolve(string $event): ?array
    {
        $reg = EmailEvents::EVENTS[$event] ?? null;
        if (! $reg) {
            return null;
        }

        $row = static::query()->where('event', $event)->first();

        return [
            'event' => $event,
            'label' => $reg['label'],
            'subject' => $row->subject ?? $reg['subject'],
            'body' => $row->body ?? $reg['body'],
            'enabled' => $row ? (bool) $row->enabled : true,
        ];
    }
}
