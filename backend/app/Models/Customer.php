<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use App\Models\Order;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'name',
    'phone',
    'provider',
    'provider_customer_id',
    'notes',
    'location',
    'status',
    'last_contact_at',
    'assigned_to',
    'lead_source',
    'district',
    'area',
    'next_follow_up_at',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['next_follow_up_at' => 'datetime'];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}