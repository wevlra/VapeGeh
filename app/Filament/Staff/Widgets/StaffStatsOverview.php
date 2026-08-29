<?php

namespace App\Filament\Staff\Widgets;

use App\Models\Income;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockEntry;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StaffStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected function period(): string
    {
        return $this->pageFilters['period'] ?? 'all';
    }

    protected function periodStart(): ?Carbon
    {
        return match ($this->period()) {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(6)->startOfDay(),
            '30days' => now()->subDays(29)->startOfDay(),
            default => null,
        };
    }

    protected function periodLabel(): string
    {
        return match ($this->period()) {
            'today' => 'Hari ini',
            '7days' => '7 hari terakhir',
            '30days' => '30 hari terakhir',
            default => 'Semua waktu',
        };
    }

    protected function getStats(): array
    {
        $period = $this->period();
        $locationId = auth()->user()->location_id;
        $start = $this->periodStart();

        $saleQuery = Sale::where('sales.location_id', $locationId);
        $stockOutQuery = StockEntry::where('stock_entries.type', 'out')->where('stock_entries.location_id', $locationId);
        $incomeQuery = Income::where('incomes.location_id', $locationId);

        if ($start) {
            $saleQuery->where('sales.created_at', '>=', $start);
            $stockOutQuery->where('stock_entries.created_at', '>=', $start);
            $incomeQuery->where('incomes.date', '>=', $start->toDateString());
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

        $totalStock = Stock::where('location_id', $locationId)->sum('qty');

        $days = match ($period) {
            'today' => 1,
            '7days' => 7,
            '30days' => 30,
            default => 6,
        };

        $chartStart = $start ?? now()->subMonths($days - 1)->startOfMonth();
        $chartEnd = now();

        $dateExprSale = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at) as day_key"
            : "DATE_FORMAT(created_at, '%Y-%m-%d') as day_key";

        $salesByDayPos = Sale::where('location_id', $locationId)
            ->whereBetween('created_at', [$chartStart->toDateTimeString(), $chartEnd->toDateTimeString()])
            ->selectRaw("{$dateExprSale}, SUM(total) as total")
            ->groupBy('day_key')
            ->pluck('total', 'day_key');

        $stockOutsDaily = StockEntry::with('items')->where('type', 'out')
            ->where('location_id', $locationId)
            ->whereBetween('created_at', [$chartStart->toDateTimeString(), $chartEnd->toDateTimeString()])
            ->get()
            ->groupBy(fn ($entry) => $entry->created_at->format('Y-m-d'));

        $chartValues = collect(range($days - 1, 0))->map(function ($i) use ($chartStart, $salesByDayPos, $stockOutsDaily) {
            $day = $chartStart->copy()->addDays($i)->format('Y-m-d');

            $posTotal = (float) ($salesByDayPos[$day] ?? 0);

            $stockOutTotal = 0;
            if (isset($stockOutsDaily[$day])) {
                foreach ($stockOutsDaily[$day] as $entry) {
                    $itemsTotal = $entry->items->sum(fn ($item) => $item->qty * $item->unit_price);
                    $costs = StockEntry::parseAdditionalCostsTotal($entry->additional_costs);
                    $stockOutTotal += $itemsTotal + $costs;
                }
            }

            return (int) ($posTotal + $stockOutTotal);
        })->toArray();

        return [
            Stat::make('Total Penjualan', $totalSales)
                ->description($this->periodLabel())
                ->chart($chartValues)
                ->color('success'),
            Stat::make('Pendapatan', 'Rp '.number_format($totalRevenue, 0, ',', '.'))
                ->description($this->periodLabel())
                ->chart($chartValues)
                ->color('success'),
            Stat::make('Unit Stok', number_format($totalStock))
                ->description('Unit di lokasi Anda')
                ->color('warning'),
        ];
    }
}
