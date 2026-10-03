<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'automation_id',
    'customer_id',
    'conversation_id',
    'message_id',
    'status',
    'scheduled_at',
    'executed_at',
    'error_message',
    'context',
])]
class AutomationRun extends Model
{
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'executed_at' => 'datetime',
            'context' => 'array',
        ];
    }

    public function automation(): BelongsTo { return $this->belongsTo(Automation::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }
    public function message(): BelongsTo { return $this->belongsTo(Message::class); }
}
