<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateStockPricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recalculate-wac-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate and fix stocks.purchase_price using Weighted Average Cost (WAC) from stock entry items';

    /**
     * Execute the console command.
     *
     * WAC is recalculated by replaying all stock_entry_items IN events chronologically.
     * We use stock_entry_items (not stock_movements) as the source of truth for purchase prices,
     * because old stock_movements may be missing unit_price or grouped incorrectly.
     *
     * OUT movements reduce qty but do NOT change WAC price.
     */
    public function handle(): void
    {
        $this->info('Starting recalculation of WAC (Weighted Average Cost) for all stocks...');

        // Get all unique product+location combinations that have stock records
        $stockKeys = DB::table('stocks')
            ->select('product_id', 'location_id')
            ->get();

        $bar = $this->output->createProgressBar(count($stockKeys));
        $updated = 0;

        foreach ($stockKeys as $key) {
            // Replay IN events from stock_entry_items chronologically
            $inEvents = DB::table('stock_entry_items')
                ->join('stock_entries', 'stock_entries.id', '=', 'stock_entry_items.stock_entry_id')
                ->where('stock_entries.type', 'in')
                ->where('stock_entry_items.product_id', $key->product_id)
                ->where('stock_entries.location_id', $key->location_id)
                ->orderBy('stock_entries.created_at')
                ->orderBy('stock_entries.id')
                ->select('stock_entry_items.qty', 'stock_entry_items.unit_price')
                ->get();

            // Replay OUT events from stock_movements chronologically (for qty tracking only)
            $outEvents = DB::table('stock_movements')
                ->where('type', 'out')
                ->where('product_id', $key->product_id)
                ->where('location_id', $key->location_id)
                ->orderBy('created_at')
                ->orderBy('id')
                ->select('quantity', 'created_at')
                ->get();

            // Merge IN and OUT events - for simplicity, compute final WAC from all INs
            // then the current WAC doesn't change from OUTs
            $runningQty = 0.0;
            $runningValue = 0.0;
            $currentWac = 0.0;

            foreach ($inEvents as $event) {
                $qty = (float) $event->qty;
                $price = (float) ($event->unit_price ?? 0);

                if ($qty <= 0 || $price <= 0) {
                    continue;
                }

                $newTotalValue = ($runningQty * $currentWac) + ($qty * $price);
                $runningQty += $qty;
                $currentWac = $runningQty > 0 ? $newTotalValue / $runningQty : $price;
            }

            if ($currentWac > 0) {
                $affected = DB::table('stocks')
                    ->where('product_id', $key->product_id)
                    ->where('location_id', $key->location_id)
                    ->update(['purchase_price' => round($currentWac, 2)]);

                if ($affected) {
                    $updated++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Done! Successfully updated purchase_price in stocks table for {$updated} records.");

        $assetTotal = DB::table('stocks')
            ->selectRaw('SUM(qty * COALESCE(purchase_price, 0)) as total')
            ->value('total');

        $this->info('Current Total Asset: Rp '.number_format($assetTotal, 0, ',', '.'));
    }
}
