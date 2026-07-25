<?php

namespace App\Filament\Staff\Pages;

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

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $locationId = auth()->user()->location_id;

        $saleQuery = Sale::query()->where('location_id', $locationId);
        $stockOutQuery = StockEntry::where('stock_entries.type', 'out')->where('stock_entries.location_id', $locationId);

        if ($this->periodStart) {
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
