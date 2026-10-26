<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'document',
        'phone',
        'email',
        'birthday',
        'points',
        'total_spent',
        'is_active',
    ];

    protected $casts = [
        'birthday' => 'date',
        'total_spent' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
