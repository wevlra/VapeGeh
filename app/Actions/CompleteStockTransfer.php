<?php

namespace App\Actions;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CompleteStockTransfer
{
    public function execute(StockTransfer $transfer, User $completedBy, ?DateTimeInterface $completedAt = null): void
    {
        if ($transfer->status !== 'pending') {
            throw new DomainException('Only pending transfers can be completed.');
        }

        if ($transfer->from_location_id === $transfer->to_location_id) {
            throw new DomainException('Transfer source and destination cannot be the same location.');
        }

        $transferDate = $completedAt ? Carbon::parse($completedAt) : ($transfer->created_at ?? now());

        DB::transaction(function () use ($transfer, $completedBy, $transferDate) {
            $transfer->load(['items.product', 'toLocation']);
            $targetPriceType = $transfer->toLocation ? $transfer->toLocation->price_type : '1';

            foreach ($transfer->items as $item) {
                // Ensure correct price based on destination location price type
                $priceField = $targetPriceType === '2' ? 'price_2' : 'price_1';
                $productPrice = $item->product ? (float) $item->product->{$priceField} : 0;
                if ($item->purchase_price === null || $item->purchase_price <= 0) {
                    $item->purchase_price = $productPrice;
                    $item->save();
                }

                $sourceStock = Stock::where('product_id', $item->product_id)
                    ->where('location_id', $transfer->from_location_id)
                    ->lockForUpdate()
                    ->first();

                if (! $sourceStock || $sourceStock->qty < $item->qty) {
                    throw new DomainException(
                        "Insufficient stock for product ID {$item->product_id} at source location."
                    );
                }

                $sourceStock->decrement('qty', $item->qty);

                $destinationStock = Stock::where('product_id', $item->product_id)
                    ->where('location_id', $transfer->to_location_id)
                    ->lockForUpdate()
                    ->first();

                if (! $destinationStock) {
                    $destinationStock = Stock::create([
                        'product_id' => $item->product_id,
                        'location_id' => $transfer->to_location_id,
                        'qty' => $item->qty,
                        'purchase_price' => $item->purchase_price > 0 ? $item->purchase_price : null,
                    ]);
                } else {
                    $destinationStock->qty += $item->qty;
                    if ($item->purchase_price !== null) {
                        $destinationStock->update(['purchase_price' => $item->purchase_price > 0 ? $item->purchase_price : null]);
                    } else {
                        $destinationStock->save();
                    }
                }

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'location_id' => $transfer->from_location_id,
                    'type' => 'transfer',
                    'quantity' => -$item->qty,
                    'related_type' => StockTransfer::class,
                    'related_id' => $transfer->id,
                    'created_by' => $completedBy->id,
                    'notes' => "Transfer #{$transfer->transfer_number}",
                    'created_at' => $transferDate,
                    'updated_at' => $transferDate,
                ]);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'location_id' => $transfer->to_location_id,
                    'type' => 'transfer',
                    'quantity' => $item->qty,
                    'related_type' => StockTransfer::class,
                    'related_id' => $transfer->id,
                    'created_by' => $completedBy->id,
                    'notes' => "Transfer #{$transfer->transfer_number}",
                    'created_at' => $transferDate,
                    'updated_at' => $transferDate,
                ]);
            }

            $transfer->status = 'completed';
            $transfer->completed_at = $transferDate;
            $transfer->save();
        });
    }
}
