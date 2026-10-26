<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommandBatch extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'area',
        'printer_name',
        'status',
        'sent_at',
        'printed_at',
        'print_error',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CommandBatchItem::class);
    }
}
