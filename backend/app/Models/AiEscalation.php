<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiEscalation extends Model
{
    protected $fillable = [
        'conversation_id',
        'customer_id',
        'order_id',
        'taken_over_by',
        'status',
        'reason',
        'intent',
        'summary',
        'products_discussed',
        'recommended_action',
        'taken_over_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'products_discussed' => 'array',
            'taken_over_at' => 'datetime',
            'resolved_at' => 'datetime',
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
}
