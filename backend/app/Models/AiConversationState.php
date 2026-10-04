<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConversationState extends Model
{
    protected $fillable = [
        'conversation_id',
        'status',
        'intent',
        'stage',
        'language',
        'confidence',
        'context',
        'summary',
        'next_action',
        'last_customer_message_at',
        'last_ai_message_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'confidence' => 'float',
            'last_customer_message_at' => 'datetime',
            'last_ai_message_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
