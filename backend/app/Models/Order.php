<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'product_id',
        'product_name',
        'product_size',
        'quantity',
        'unit_price',
        'total_amount',
        'status',
        'payment_status',
        'delivery_status',
        'delivery_assigned_to',
        'delivery_address',
        'delivery_area',
        'delivered_at',
        'delivery_notes',
        'delivery_proof_path',
        'notes',
        'ordered_at',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'ordered_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function deliveryAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_assigned_to');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}