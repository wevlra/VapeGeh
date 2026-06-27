<?php

namespace App\Models;

use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'type', 'address', 'status'])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

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
            ->leftJoin('product_prices', function ($join) {
                $join->on('product_prices.product_id', '=', 'products.id')
                    ->whereRaw('product_prices.price = (SELECT MAX(p2.price) FROM product_prices p2 WHERE p2.product_id = products.id)');
            })
            ->where('stocks.location_id', $this->id)
            ->selectRaw('SUM(stocks.qty * COALESCE(product_prices.price, 0)) as total')
            ->value('total') ?? 0;
    }

    public static function getTotalAssetOfAll(): float
    {
        return (float) DB::table('stocks')
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->leftJoin('product_prices', function ($join) {
                $join->on('product_prices.product_id', '=', 'products.id')
                    ->whereRaw('product_prices.price = (SELECT MAX(p2.price) FROM product_prices p2 WHERE p2.product_id = products.id)');
            })
            ->selectRaw('SUM(stocks.qty * COALESCE(product_prices.price, 0)) as total')
            ->value('total') ?? 0;
    }

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }
}
