<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegister extends Model
{
    protected $fillable = [
        'user_id',
        'opening_amount',
        'closing_amount',
        'sales_total',
        'status',
        'opened_at',
        'closed_at',
        'expected_amount',
        'difference_amount',
    ];

    protected $casts = [
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'sales_total' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'expected_amount' => 'decimal:2',
        'difference_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function movements()
    {
    return $this->hasMany(CashMovement::class);
    }
}
