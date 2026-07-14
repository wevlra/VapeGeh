<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'category_id', 'purchase_price', 'selling_price', 'reseller_price', 'price_1', 'price_2'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return HasMany<Stock, $this>
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the default selling price — falls back to first ProductPrice if selling_price is 0.
     */
    public function getDefaultPriceAttribute(): float
    {
        return (float) $this->selling_price;
    }

    protected function casts(): array
    {
        return [
            'purchase_price' => 'float',
            'selling_price' => 'float',
            'reseller_price' => 'float',
            'price_1' => 'float',
            'price_2' => 'float',
        ];
    }
}
