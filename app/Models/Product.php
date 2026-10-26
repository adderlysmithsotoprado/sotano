<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'price',
        'cost',
        'stock',
        'printer_area',
        'is_active',
        'is_favorite',
        'cost',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
        'is_favorite' => 'boolean',
        'cost' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

   public function modifiers(): BelongsToMany
{
    return $this->belongsToMany(
        Modifier::class,
        'modifier_product'
    );
}

    public function stockMovements()
    {
    return $this->hasMany(StockMovement::class);
    }

}
