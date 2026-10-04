<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFollowUp extends Model
{
    protected $fillable = [
        'conversation_id',
        'customer_id',
        'message_id',
        'status',
        'attempt',
        'scheduled_at',
        'sent_at',
        'idempotency_key',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
