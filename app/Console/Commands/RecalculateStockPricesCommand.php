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
    protected $description = 'Recalculate and fix stocks.price using Weighted Average Cost (WAC) from stock movements';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting recalculation of WAC (Weighted Average Cost) for all stocks...');

        // Fetch all "in" stock movements
        $movements = DB::table('stock_movements')
            ->where('type', 'in')
            ->select(
                'product_id',
                'location_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(quantity * unit_price) as total_value')
            )
            ->groupBy('product_id', 'location_id')
            ->get();

        $updated = 0;
        $bar = $this->output->createProgressBar(count($movements));

        foreach ($movements as $m) {
            if ($m->total_qty > 0) {
                $wac = $m->total_value / $m->total_qty;

                $affected = DB::table('stocks')
                    ->where('product_id', $m->product_id)
                    ->where('location_id', $m->location_id)
                    ->update(['purchase_price' => round($wac, 2)]);

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
