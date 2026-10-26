<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'restaurant_table_id',
        'user_id',
        'status',
        'ready',
        'command_sent',
        'total',
        'notes',
        'paid_at',
        'printed_at',
        'ticket_printed_at',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'customer_id',
        'guest_name',
        'amount_received',
        'change_amount',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'ready' => 'boolean',
        'command_sent' => 'boolean',
        'paid_at' => 'datetime',
        'printed_at' => 'datetime',
        'ticket_printed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'amount_received' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(
            RestaurantTable::class,
            'restaurant_table_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
    return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
    return $this->hasMany(OrderPayment::class);
    }
    public function commandBatches()
    {
    return $this->hasMany(CommandBatch::class);
    }
}
