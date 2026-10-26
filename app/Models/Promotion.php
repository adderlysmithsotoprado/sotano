<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'name',
        'type',
        'product_id',
        'minimum_quantity',
        'promotional_unit_price',
        'discount_percentage',
        'discount_amount',
        'days',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'days' => 'array',
        'is_active' => 'boolean',
        'minimum_quantity' => 'integer',
        'promotional_unit_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
