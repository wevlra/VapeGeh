<?php

namespace App\Actions;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CompleteStockTransfer
{
    public function execute(StockTransfer $transfer, User $completedBy): void
    {
        if ($transfer->status !== 'pending') {
            throw new DomainException('Only pending transfers can be completed.');
        }

        DB::transaction(function () use ($transfer, $completedBy) {
            $transfer->load('items');

            foreach ($transfer->items as $item) {
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
                    ]);
                } else {
                    $destinationStock->increment('qty', $item->qty);
                }

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'location_id' => $transfer->from_location_id,
                    'type' => 'transfer_out',
                    'quantity' => -$item->qty,
                    'related_type' => StockTransfer::class,
                    'related_id' => $transfer->id,
                    'notes' => "Transfer #{$transfer->transfer_number}",
                    'created_by' => $completedBy->id,
                ]);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'location_id' => $transfer->to_location_id,
                    'type' => 'transfer_in',
                    'quantity' => $item->qty,
                    'related_type' => StockTransfer::class,
                    'related_id' => $transfer->id,
                    'notes' => "Transfer #{$transfer->transfer_number}",
                    'created_by' => $completedBy->id,
                ]);
            }

            $transfer->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        });
    }
}
