<?php

namespace App\Actions;

use App\Models\Stock;
use App\Models\StockMovement;
use DomainException;
use Illuminate\Support\Facades\DB;

class AdjustStock
{
    /**
     * @param  Stock  $stock  The stock record to adjust
     * @param  int  $newQty  The new quantity value
     * @param  string|null  $notes  Optional notes for the adjustment
     */
    public function execute(Stock $stock, int $newQty, ?string $notes = null): void
    {
        if ($newQty < 0) {
            throw new DomainException('Stock quantity cannot be negative.');
        }

        $oldQty = $stock->qty;
        $difference = $newQty - $oldQty;

        if ($difference === 0) {
            return;
        }

        DB::transaction(function () use ($stock, $newQty, $notes, $difference) {
            $lockedStock = Stock::where('id', $stock->id)->lockForUpdate()->first();

            if (! $lockedStock || $lockedStock->qty + $difference !== $newQty) {
                throw new DomainException('Stock changed concurrently. Please retry.');
            }

            $lockedStock->update(['qty' => $newQty]);

            StockMovement::create([
                'product_id' => $lockedStock->product_id,
                'location_id' => $lockedStock->location_id,
                'type' => 'adjustment',
                'quantity' => $difference,
                'notes' => $notes,
                'related_type' => Stock::class,
                'related_id' => $lockedStock->id,
            ]);
        });
    }
}
