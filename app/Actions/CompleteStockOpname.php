<?php

namespace App\Actions;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockOpname;
use DomainException;
use Illuminate\Support\Facades\DB;

class CompleteStockOpname
{
    public function execute(StockOpname $opname): void
    {
        if ($opname->status !== 'draft') {
            throw new DomainException('Stock opname sudah selesai.');
        }

        DB::transaction(function () use ($opname) {
            $lockedOpname = StockOpname::where('id', $opname->id)
                ->lockForUpdate()
                ->with('items.product')
                ->firstOrFail();

            foreach ($lockedOpname->items as $item) {
                if ($item->difference === 0) {
                    continue;
                }

                $stock = Stock::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'location_id' => $lockedOpname->location_id,
                    ],
                    ['qty' => 0]
                );

                $stock->qty = $item->actual_qty;
                $stock->save();

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'location_id' => $lockedOpname->location_id,
                    'type' => 'opname',
                    'quantity' => $item->difference,
                    'notes' => "Penyesuaian stok fisik (opname {$lockedOpname->opname_number})",
                    'related_type' => StockOpname::class,
                    'related_id' => $lockedOpname->id,
                ]);
            }

            $totalDifferenceValue = $lockedOpname->total_difference_value;

            if ($totalDifferenceValue < 0) {
                Expense::create([
                    'location_id' => $lockedOpname->location_id,
                    'category' => 'other',
                    'description' => "Rugi penyesuaian stok (opname {$lockedOpname->opname_number})",
                    'amount' => abs($totalDifferenceValue),
                    'date' => now(),
                ]);
            } elseif ($totalDifferenceValue > 0) {
                Income::create([
                    'location_id' => $lockedOpname->location_id,
                    'category' => 'other',
                    'description' => "Untung penyesuaian stok (opname {$lockedOpname->opname_number})",
                    'amount' => $totalDifferenceValue,
                    'date' => now(),
                ]);
            }

            $lockedOpname->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Update original instance so Filament form sees the changes
            $opname->status = 'completed';
            $opname->completed_at = $lockedOpname->completed_at;
            $opname->load('items.product');
        });
    }
}
