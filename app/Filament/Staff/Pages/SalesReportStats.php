<?php

namespace App\Filament\Staff\Pages;

use App\Filament\Traits\HasCustomPeriodFilters;
use App\Models\Sale;
use App\Models\StockEntry;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SalesReportStats extends StatsOverviewWidget
{
    use HasCustomPeriodFilters;
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $start = $this->periodStart();
        $end = $this->periodEnd();

        $locationId = auth()->user()->location_id;

        $saleQuery = Sale::query()->where('location_id', $locationId);
        $stockOutQuery = StockEntry::where('stock_entries.type', 'out')->where('stock_entries.location_id', $locationId);

        if ($start) {
            $saleQuery->where('created_at', '>=', $start);
            $stockOutQuery->where('stock_entries.created_at', '>=', $start);
        }
        if ($end) {
            $saleQuery->where('created_at', '<=', $end);
            $stockOutQuery->where('stock_entries.created_at', '<=', $end);
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

        $periodLabel = $this->periodLabel();

        return [
            Stat::make('Total Penjualan', number_format($totalSales))
                ->description($periodLabel)
                ->color('success'),
            Stat::make('Total Pendapatan', 'Rp '.number_format($totalRevenue, 0, ',', '.'))
                ->description($periodLabel)
                ->color('success'),
        ];
    }
}
