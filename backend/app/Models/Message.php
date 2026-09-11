<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'conversation_id',
    'direction',
    'message_type',
    'body',
    'provider_message_id',
    'status',
    'sent_at',
    'read_at',
    'media_id',
    'media_url',
    'media_mime_type',
    'media_filename',
    'media_caption',
    'latitude',
    'longitude',
    'location_name',
    'location_address',
])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}