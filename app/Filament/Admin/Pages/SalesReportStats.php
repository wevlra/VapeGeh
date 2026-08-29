<?php

namespace App\Filament\Admin\Pages;

use App\Models\Location;
use App\Models\Sale;
use App\Models\StockEntry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class SalesReportStats extends StatsOverviewWidget
{
    #[Reactive]
    public ?string $period = 'all';

    #[Reactive]
    public ?string $periodStart = null;

    #[Reactive]
    public array $dateFilter = [];

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $saleQuery = Sale::query();
        $stockOutQuery = StockEntry::where('stock_entries.type', 'out');

        $from = $this->dateFilter['from'] ?? null;
        $until = $this->dateFilter['until'] ?? null;

        if (filled($from) || filled($until)) {
            if (filled($from)) {
                $saleQuery->whereDate('created_at', '>=', $from);
                $stockOutQuery->whereDate('stock_entries.created_at', '>=', $from);
            }
            if (filled($until)) {
                $saleQuery->whereDate('created_at', '<=', $until);
                $stockOutQuery->whereDate('stock_entries.created_at', '<=', $until);
            }
        } elseif ($this->periodStart) {
            $saleQuery->where('created_at', '>=', $this->periodStart);
            $stockOutQuery->where('stock_entries.created_at', '>=', $this->periodStart);
        }

        $totalSales = $saleQuery->count() + $stockOutQuery->count();
        $posRevenue = (float) $saleQuery->sum('total');

        $stockOutItemsRevenue = (clone $stockOutQuery)
            ->join('stock_entry_items', 'stock_entries.id', '=', 'stock_entry_items.stock_entry_id')
            ->sum(DB::raw('stock_entry_items.qty * stock_entry_items.unit_price'));

        $stockOutAdditionalCosts = (clone $stockOutQuery)
            ->pluck('additional_costs')
            ->sum(fn ($costs) => StockEntry::parseAdditionalCostsTotal($costs));

        $stockOutRevenue = (float) $stockOutItemsRevenue + (float) $stockOutAdditionalCosts;

        $totalRevenue = $posRevenue + $stockOutRevenue;

        $periodLabel = match ($this->period) {
            'today' => 'Hari Ini',
            'week' => '7 Hari Terakhir',
            'month' => '30 Hari Terakhir',
            default => 'Semua Waktu',
        };

        $stats = [
            Stat::make('Total Penjualan', number_format($totalSales))
                ->description($periodLabel)
                ->color('success'),
            Stat::make('Total Pendapatan', 'Rp '.number_format($totalRevenue, 0, ',', '.'))
                ->description($periodLabel)
                ->color('success'),
        ];

        $locations = Location::all();
        foreach ($locations as $location) {
            $locSaleQuery = clone $saleQuery;
            $locStockOutQuery = clone $stockOutQuery;

            $locSalesCount = $locSaleQuery->where('location_id', $location->id)->count()
                + $locStockOutQuery->where('stock_entries.location_id', $location->id)->count();

            // Re-clone because count() might have modified the builder state although we cloned it,
            // wait, we applied where('location_id'), which modifies the cloned query.
            // So we can just use the modified loc queries to calculate sums.
            $locPosRevenue = (float) $locSaleQuery->sum('total');

            $locStockOutItemsRevenue = (clone $locStockOutQuery)
                ->join('stock_entry_items', 'stock_entries.id', '=', 'stock_entry_items.stock_entry_id')
                ->sum(DB::raw('stock_entry_items.qty * stock_entry_items.unit_price'));

            $locStockOutAdditionalCosts = (clone $locStockOutQuery)
                ->pluck('additional_costs')
                ->sum(fn ($costs) => StockEntry::parseAdditionalCostsTotal($costs));

            $locStockOutRevenue = (float) $locStockOutItemsRevenue + (float) $locStockOutAdditionalCosts;
            $locTotalRevenue = $locPosRevenue + $locStockOutRevenue;

            if ($locSalesCount > 0 || $locTotalRevenue > 0) {
                $stats[] = Stat::make("Penjualan {$location->name}", 'Rp '.number_format($locTotalRevenue, 0, ',', '.'))
                    ->description($locSalesCount.' Transaksi')
                    ->color('info');
            }
        }

        return $stats;
    }
}
