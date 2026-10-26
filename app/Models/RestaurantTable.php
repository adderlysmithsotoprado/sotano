<?php

namespace App\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantTable extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'status',
        'reserved_by',
        'reserved_at',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'reserved_at' => 'datetime',
    ];
    public function orders(): HasMany
    {
    return $this->hasMany(Order::class, 'restaurant_table_id');
    }
}
