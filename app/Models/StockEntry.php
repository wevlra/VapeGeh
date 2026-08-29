<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'location_id', 'vendor_id', 'buyer_id', 'payment_method', 'notes', 'additional_costs', 'created_by'])]
class StockEntry extends Model
{
    use HasUuids;

    public function items(): HasMany
    {
        return $this->hasMany(StockEntryItem::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'additional_costs' => 'array',
        ];
    }

    public function getAdditionalCostsTotalAttribute(): float
    {
        return static::parseAdditionalCostsTotal($this->additional_costs);
    }

    public function getTotalAmountAttribute(): float
    {
        $itemsTotal = (float) $this->items->sum(fn ($item) => $item->qty * $item->unit_price);

        return $itemsTotal + $this->additional_costs_total;
    }

    public static function parseAdditionalCostsTotal(array|string|null $additionalCosts): float
    {
        if (empty($additionalCosts)) {
            return 0;
        }

        $costs = is_string($additionalCosts) ? json_decode($additionalCosts, true) : $additionalCosts;
        if (! is_array($costs)) {
            return 0;
        }

        $total = 0;
        foreach ($costs as $cost) {
            $total += (float) ($cost['amount'] ?? 0);
        }

        return $total;
    }
}
