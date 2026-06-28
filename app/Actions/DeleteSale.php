<?php

namespace App\Actions;

use App\Models\Sale;
use App\Models\Stock;
use DomainException;
use Illuminate\Support\Facades\DB;

class DeleteSale
{
    public function execute(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $sale->load('items', 'stockMovements');

            foreach ($sale->items as $item) {
                $movement = $sale->stockMovements
                    ->firstWhere('product_id', $item->product_id);

                if (! $movement) {
                    throw new DomainException(
                        "Stock movement not found for product ID {$item->product_id}."
                    );
                }

                $stock = Stock::where('product_id', $item->product_id)
                    ->where('location_id', $sale->location_id)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    $stock->qty += $item->qty;
                    $stock->save();
                } else {
                    Stock::create([
                        'product_id' => $item->product_id,
                        'location_id' => $sale->location_id,
                        'qty' => $item->qty,
                    ]);
                }
            }

            $sale->stockMovements()->delete();
            $sale->items()->delete();
            $sale->delete();
        });
    }
}
