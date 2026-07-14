<?php

namespace App\Models;

use Database\Factories\StockOpnameItemFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(StockOpnameItemFactory::class)]
class StockOpnameItem extends Model
{
    /** @use HasFactory<StockOpnameItemFactory> */
    use HasFactory;

    protected $with = ['product', 'stockOpname'];

    protected $fillable = [
        'stock_opname_id',
        'product_id',
        'system_qty',
        'actual_qty',
        'difference',
        'notes',
    ];

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getDifferenceValueAttribute(): float
    {
        if ($this->difference > 0) {
            $priceField = 'purchase_price';
        } else {
            $priceField = $this->stockOpname?->price_type ?? 'purchase_price';
        }

        return (float) ($this->difference * ($this->product?->{$priceField} ?? 0));
    }

    protected function casts(): array
    {
        return [
            'system_qty' => 'integer',
            'actual_qty' => 'integer',
            'difference' => 'integer',
        ];
    }
}
