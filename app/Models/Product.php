<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'category_id', 'purchase_price', 'reseller_price', 'price_1', 'price_2', 'status'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUuids;

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

    public function getDefaultPriceAttribute(): float
    {
        return (float) $this->price_1;
    }

    protected function casts(): array
    {
        return [
            'purchase_price' => 'float',
            'price_1' => 'float',
            'reseller_price' => 'float',
            'price_1' => 'float',
            'price_2' => 'float',
            'status' => 'string',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Product $product) {
            if ($product->movements()->exists() || $product->stocks()->exists()) {
                throw new DomainException('Produk ini terkait dengan riwayat stok/penjualan. Silakan ubah status menjadi Nonaktif alih-alih menghapus.');
            }
        });
    }
}
