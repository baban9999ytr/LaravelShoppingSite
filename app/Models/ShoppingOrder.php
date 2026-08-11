<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingOrder extends Model
{
    protected $fillable = [
        'user_id',
        'origin_city',
        'destination_city',
        'ordered_at',
        'estimated_arrival_at',
        'items', 
    ];

    protected $casts = [
        'items' => 'array',
    ];
}