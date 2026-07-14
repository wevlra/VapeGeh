<?php

namespace App\Models;

use Database\Factories\StockOpnameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(StockOpnameFactory::class)]
#[Fillable(['opname_number', 'location_id', 'price_type', 'created_by', 'status', 'notes', 'completed_at'])]
class StockOpname extends Model
{
    /** @use HasFactory<StockOpnameFactory> */
    use HasFactory;

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalDifferenceValueAttribute(): float
    {
        return $this->items->sum(function (StockOpnameItem $item) {
            if ($item->difference > 0) {
                $priceField = 'purchase_price';
            } else {
                $priceField = $this->price_type ?? 'purchase_price';
            }

            return $item->difference * ($item->product?->{$priceField} ?? 0);
        });
    }

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }
}
