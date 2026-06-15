<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public const STATUSES = ['open' => 'Open', 'answered' => 'Answered', 'closed' => 'Closed'];

    public const PRIORITIES = ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'];

    public const CATEGORIES = ['general' => 'General', 'billing' => 'Billing', 'technical' => 'Technical', 'feature' => 'Feature request'];

    protected $fillable = ['user_id', 'subject', 'status', 'priority', 'category', 'last_reply_at', 'last_reply_by'];

    protected function casts(): array
    {
        return ['last_reply_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('id');
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Append a message and move the ticket's status to reflect who replied:
     * a staff reply marks it "answered"; a customer reply (re)opens it.
     */
    public function addMessage(string $body, string $role, ?int $userId): TicketMessage
    {
        $message = $this->messages()->create([
            'user_id' => $userId,
            'author_role' => $role,
            'body' => $body,
            'created_at' => now(),
        ]);

        $this->forceFill([
            'status' => $role === 'admin' ? 'answered' : 'open',
            'last_reply_at' => now(),
            'last_reply_by' => $role,
        ])->save();

        return $message;
    }
}
