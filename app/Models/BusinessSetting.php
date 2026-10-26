<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = [
        'business_name',
        'ruc',
        'address',
        'phone',
        'logo_path',
        'ticket_message',
        'kitchen_printer',
        'bar_printer',
        'ticket_printer',
    ];
}
