<?php

namespace App\Actions;

use App\Models\Buyer;
use App\Models\Income;
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
                    $movement = $sale->stockMovements->firstWhere('product_id', $item->product_id);
                    $returnPrice = $movement ? (float) $movement->unit_price : 0;

                    $oldStockQty = $stock->qty;
                    $oldStockPrice = (float) $stock->purchase_price;
                    $returnQty = $item->qty;

                    $newTotalQty = $oldStockQty + $returnQty;
                    $wac = $newTotalQty > 0
                        ? (($oldStockQty * $oldStockPrice) + ($returnQty * $returnPrice)) / $newTotalQty
                        : $returnPrice;

                    $stock->qty = $newTotalQty;
                    $stock->purchase_price = round($wac, 2);
                    $stock->save();
                } else {
                    $movement = $sale->stockMovements->firstWhere('product_id', $item->product_id);
                    Stock::create([
                        'product_id' => $item->product_id,
                        'location_id' => $sale->location_id,
                        'qty' => $item->qty,
                        'purchase_price' => $movement ? $movement->unit_price : null,
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
                $productId = $item['product_id'];
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
                    'unit_price' => $stock->purchase_price,
                    'related_type' => Sale::class,
                    'related_id' => $sale->id,
                    'notes' => "Sale {$sale->invoice_number}",
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

            Income::where('description', 'like', "Penjualan Kasir {$sale->invoice_number}%")->delete();

            if ($total > 0) {
                $buyer = $sale->buyer_id ? Buyer::find($sale->buyer_id) : null;
                $buyerName = $buyer ? " - {$buyer->name}" : '';
                Income::create([
                    'location_id' => $sale->location_id,
                    'category' => 'sale',
                    'description' => "Penjualan Kasir {$sale->invoice_number}{$buyerName}",
                    'amount' => $total,
                    'date' => $sale->created_at ?? now(),
                    'created_by' => $sale->user_id ?? auth()->id(),
                ]);
            }
        });
    }
}
