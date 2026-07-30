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

#[Fillable(['name', 'type', 'address', 'phone', 'status'])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory, HasUuids;

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
     * Total asset value: sum of (qty * price) for all stocks at this location.
     * Price uses the highest available selling price (Store tier) per product.
     */
    public function getTotalAssetAttribute(): float
    {
        return (float) DB::table('stocks')
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->where('stocks.location_id', $this->id)
            ->selectRaw('SUM(stocks.qty * COALESCE(products.purchase_price, 0)) as total')
            ->value('total') ?? 0;
    }

    public static function getTotalAssetOfAll(): float
    {
        return (float) DB::table('stocks')
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->selectRaw('SUM(stocks.qty * COALESCE(products.purchase_price, 0)) as total')
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
