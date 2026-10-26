<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
    'order_id',
    'product_id',
    'product_name',
    'unit_price',
    'quantity',
    'subtotal',
    'printer_area',
    'notes',
    'status',
    'command_sent',
    'printed_at',
    'preparing_at',
    'ready_at',
    'delivered_at',
    'unit_cost',
    'profit',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'command_sent' => 'boolean',
        'printed_at' => 'datetime',

        'preparing_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',

        'unit_cost' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function modifiers()
    {
    return $this->hasMany(OrderItemModifier::class);
    }
    public function commandBatchItems()
    {
    return $this->hasMany(CommandBatchItem::class);
    }
}
