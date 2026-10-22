<?php

namespace App\Models;

use Database\Factories\LocationFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'type', 'price_type', 'address', 'phone', 'status'])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory, HasUuids;

    /**
     * @var array<string, string>
     */
    public const PRICE_TYPE_OPTIONS = [
        '1' => 'Harga 1',
        '2' => 'Harga 2',
        'reseller' => 'Harga Reseller',
    ];

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Stock, $this>
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Product price column used by this location.
     */
    public function priceField(): string
    {
        return match ($this->price_type) {
            '2' => 'price_2',
            'reseller' => 'reseller_price',
            default => 'price_1',
        };
    }

    /**
     * Price column to use for a product, falling back to Harga 1 when the
     * location's tier has no price set for that product.
     */
    public function priceFieldFor(Product $product): string
    {
        $priceField = $this->priceField();

        if ($priceField !== 'price_1' && (float) ($product->{$priceField} ?? 0) <= 0) {
            return 'price_1';
        }

        return $priceField;
    }

    /**
     * Resolve the selling price for a product based on this location's price type.
     */
    public function priceFor(Product $product): float
    {
        return (float) ($product->{$this->priceFieldFor($product)} ?? 0);
    }

    /**
     * Total asset value: sum of (qty * price) for all stocks at this location.
     * Price uses the weighted average cost (WAC) stored in stocks.price per location.
     */
    public function getTotalAssetAttribute(): float
    {
        return (float) DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.location_id', $this->id)
            ->selectRaw('SUM(stocks.qty * COALESCE(stocks.purchase_price, products.purchase_price, 0)) as total')
            ->value('total') ?? 0;
    }

    public static function getTotalAssetOfAll(): float
    {
        return (float) DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->selectRaw('SUM(stocks.qty * COALESCE(stocks.purchase_price, products.purchase_price, 0)) as total')
            ->value('total') ?? 0;
    }

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Location $location) {
            if ($location->stocks()->exists() || $location->users()->exists()) {
                throw new DomainException('Lokasi ini terkait dengan data stok/staf. Silakan ubah status menjadi Nonaktif alih-alih menghapus.');
            }
        });
    }
}
