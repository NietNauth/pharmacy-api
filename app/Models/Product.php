<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'sku',
        'usage',
        'notes',
        'requires_prescription',
        'status',
        'base_price',
        'sale_price',
        'unit',
        'dosage_form',
        'active_ingredient',
        'manufacturer',
        'avg_rating',
        'review_count',
    ];

    protected $casts = [
        'requires_prescription' => 'boolean',
        'status' => ProductStatus::class,
        'base_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'avg_rating' => 'decimal:2',
        'review_count' => 'integer',
    ];

    protected function currentPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->sale_price ?? $this->base_price,
        );
    }

    protected function primaryImage(): Attribute
    {
        return Attribute::make(
            get: function () {
                $image = $this->images->where('is_primary', true)->first() ?? $this->images->first();
                if (!$image) return null;
                
                $url = $image->url;
                if (str_starts_with($url, 'http')) {
                    return $url;
                }
                
                return \Storage::url($url);
            },
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function qas(): HasMany
    {
        return $this->hasMany(ProductQa::class);
    }
}
