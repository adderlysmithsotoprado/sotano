<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandBatchItem extends Model
{
    protected $fillable = [
        'command_batch_id',
        'order_item_id',
        'product_name',
        'quantity',
        'notes',
        'modifiers',
        'status',
        'delivered_at',
    ];

    protected $casts = [
        'modifiers' => 'array',
        'delivered_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            CommandBatch::class,
            'command_batch_id'
        );
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
