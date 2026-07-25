<?php

namespace App\Actions;

use App\Models\Buyer;
use App\Models\Income;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateSale
{
    /**
     * @param  array<int, array{product_id: string, qty: int}>  $items
     */
    public function execute(
        User $user,
        string $locationId,
        array $items,
        string $paymentMethod = 'cash',
        float $paidAmount = 0,
        ?string $notes = null,
        ?string $buyerId = null,
        ?DateTimeInterface $createdAt = null,
    ): Sale {
        return DB::transaction(function () use ($user, $locationId, $items, $paymentMethod, $paidAmount, $notes, $buyerId, $createdAt) {
            $total = 0;
            $saleItems = [];
            $saleDate = $createdAt ? Carbon::parse($createdAt) : now();

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

                $price = $stock && $stock->price > 0 ? (float) $stock->price : (float) $product->price_1;
                $subtotal = $price * $item['qty'];
                $total += $subtotal;

                $saleItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'qty' => $item['qty'],
                    'price' => $price,
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
                'buyer_id' => $buyerId,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'created_at' => $saleDate,
                'updated_at' => $saleDate,
            ]);

            $buyer = $buyerId ? Buyer::find($buyerId) : null;
            $buyerName = $buyer ? " - {$buyer->name}" : '';
            Income::create([
                'location_id' => $locationId,
                'category' => 'sale',
                'description' => "Penjualan Kasir {$sale->invoice_number}{$buyerName}",
                'amount' => $total,
                'date' => $saleDate->format('Y-m-d'),
                'created_by' => $user->id,
                'created_at' => $saleDate,
                'updated_at' => $saleDate,
            ]);

            foreach ($saleItems as $saleItem) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $saleItem['product_id'],
                    'qty' => $saleItem['qty'],
                    'price' => $saleItem['price'],
                    'subtotal' => $saleItem['subtotal'],
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
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
                    'buyer_id' => $buyerId,
                    'created_by' => $user->id,
                    'notes' => "Penjualan #{$sale->invoice_number}",
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
                ]);
            }

            return $sale->load('items');
        });
    }
}
