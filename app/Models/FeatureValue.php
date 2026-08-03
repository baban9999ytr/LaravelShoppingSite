<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FeatureValue extends Model
{
    use HasFactory;

    protected $fillable = ['feature_id', 'value'];

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'feature_value_product');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'feature_value_product_variant');
    }
}
