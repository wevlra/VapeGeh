<?php

namespace App\Actions;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use DomainException;
use Illuminate\Support\Facades\DB;

class UpdateSale
{
    /**
     * Reconcile stock when a sale is edited.
     *
     * 1. Reverse old stock movements (restore stock).
     * 2. Delete old items and movements.
     * 3. Create new items and deduct stock.
     */
    public function execute(Sale $sale, array $data): void
    {
        DB::transaction(function () use ($sale, $data) {
            $sale->load('items', 'stockMovements');

            // 1. Restore stock from old sale
            foreach ($sale->items as $item) {
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

            // 2. Delete old movements and items
            $sale->stockMovements()->delete();
            $sale->items()->delete();

            // 3. Create new items and deduct stock
            $total = 0;
            $newItems = $data['items'] ?? [];
            $newStockMovements = [];

            foreach ($newItems as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) $item['qty'];
                $price = (float) $item['price'];

                if ($qty <= 0) {
                    continue;
                }

                $stock = Stock::where('product_id', $productId)
                    ->where('location_id', $sale->location_id)
                    ->lockForUpdate()
                    ->first();

                if (! $stock || $stock->qty < $qty) {
                    throw new DomainException(
                        "Insufficient stock for product ID {$productId}."
                    );
                }

                $subtotal = $price * $qty;
                $total += $subtotal;

                $stock->qty -= $qty;
                $stock->save();

                $newStockMovements[] = [
                    'product_id' => $productId,
                    'location_id' => $sale->location_id,
                    'type' => 'out',
                    'quantity' => -$qty,
                    'related_type' => Sale::class,
                    'related_id' => $sale->id,
                    'notes' => "Sale {$sale->invoice_number}",
                    'created_by' => auth()->id(),
                ];

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $productId,
                    'qty' => $qty,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ]);
            }

            foreach ($newStockMovements as $movement) {
                StockMovement::create($movement);
            }

            $sale->update([
                'total' => $total,
                'payment_method' => $data['payment_method'] ?? $sale->payment_method,
                'paid_amount' => $data['paid_amount'] ?? $sale->paid_amount,
                'notes' => $data['notes'] ?? $sale->notes,
            ]);
        });
    }
}
