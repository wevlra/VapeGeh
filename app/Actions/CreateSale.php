<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateSale
{
    /**
     * @param  array<int, array{product_id: int, qty: int}>  $items
     */
    public function execute(
        User $user,
        int $locationId,
        array $items,
        string $paymentMethod = 'cash',
        float $paidAmount = 0,
        ?string $notes = null,
    ): Sale {
        return DB::transaction(function () use ($user, $locationId, $items, $paymentMethod, $paidAmount, $notes) {
            $total = 0;
            $saleItems = [];

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $stock = Stock::where('product_id', $product->id)
                    ->where('location_id', $locationId)
                    ->first();

                if (! $stock || $stock->qty < $item['qty']) {
                    throw new DomainException(
                        "Insufficient stock for product \"{$product->name}\" at this location."
                    );
                }

                $subtotal = $product->selling_price * $item['qty'];
                $total += $subtotal;

                $saleItems[] = [
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                    'price' => $product->selling_price,
                    'subtotal' => $subtotal,
                ];
            }

            $sale = Sale::create([
                'user_id' => $user->id,
                'location_id' => $locationId,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
            ]);

            foreach ($saleItems as $saleItem) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    ...$saleItem,
                ]);

                $stock = Stock::where('product_id', $saleItem['product_id'])
                    ->where('location_id', $locationId)
                    ->first();
                $stock->decrement('qty', $saleItem['qty']);

                StockMovement::create([
                    'product_id' => $saleItem['product_id'],
                    'location_id' => $locationId,
                    'type' => 'out',
                    'quantity' => -$saleItem['qty'],
                    'related_type' => Sale::class,
                    'related_id' => $sale->id,
                    'notes' => "Sale #{$sale->invoice_number}",
                    'created_by' => $user->id,
                ]);
            }

            return $sale->load('items');
        });
    }
}
