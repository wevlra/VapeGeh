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

        if ($transfer->from_location_id === $transfer->to_location_id) {
            throw new DomainException('Transfer source and destination cannot be the same location.');
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
                    'created_by' => $completedBy->id,
                    'notes' => "Transfer #{$transfer->transfer_number}",
                ]);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'location_id' => $transfer->to_location_id,
                    'type' => 'transfer_in',
                    'quantity' => $item->qty,
                    'related_type' => StockTransfer::class,
                    'related_id' => $transfer->id,
                    'created_by' => $completedBy->id,
                    'notes' => "Transfer #{$transfer->transfer_number}",
                ]);
            }

            $transfer->status = 'completed';
            $transfer->completed_at = now();
            $transfer->save();
        });
    }
}
