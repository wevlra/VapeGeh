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
                    ->lockForUpdate()
                    ->first();

                if (! $stock || $stock->qty < $item['qty']) {
                    throw new DomainException(
                        "Insufficient stock for product \"{$product->name}\" at this location."
                    );
                }

                $defaultPrice = $product->prices->first()?->price ?? 0;
                $subtotal = $defaultPrice * $item['qty'];
                $total += $subtotal;

                $saleItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'qty' => $item['qty'],
                    'price' => $defaultPrice,
                    'subtotal' => $subtotal,
                    'stock' => $stock,
                ];
            }

            if ($total <= 0) {
                throw new DomainException('Sale total must be greater than zero. Ensure the product has a price set.');
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
                    'product_id' => $saleItem['product_id'],
                    'qty' => $saleItem['qty'],
                    'price' => $saleItem['price'],
                    'subtotal' => $saleItem['subtotal'],
                ]);

                $stock = $saleItem['stock'];
                $stock->qty -= $saleItem['qty'];
                $stock->save();

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
