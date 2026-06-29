<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'purchase_price', 'selling_price'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            $product->sku ??= 'PRD-'.Str::upper(Str::random(8));
        });
    }

    /**
     * @return HasMany<ProductPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * @return HasMany<Stock, $this>
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
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
        $sellingPrice = (float) $this->selling_price;

        return $sellingPrice > 0 ? $sellingPrice : (float) ($this->prices()->first()?->price ?? 0);
    }

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
        ];
    }
}
