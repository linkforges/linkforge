<?php

namespace App\Models;

use App\Jobs\SendWebhook;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    protected $fillable = ['user_id', 'url', 'events', 'secret', 'is_active'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Queue every active webhook of the given user that subscribes to $event.
     * Delivery is HMAC-signed and retried by the SendWebhook job.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fire(int $userId, string $event, array $payload): void
    {
        foreach (static::where('user_id', $userId)->where('is_active', true)->get() as $webhook) {
            if (in_array($event, (array) $webhook->events, true)) {
                SendWebhook::dispatch($webhook->id, $event, $payload);
            }
        }
    }
}
