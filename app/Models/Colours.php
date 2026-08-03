<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Colour extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'hex_code',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'colour_product');
    }
}
