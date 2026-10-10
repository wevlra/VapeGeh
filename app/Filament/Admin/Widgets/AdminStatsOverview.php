<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Traits\HasCustomPeriodFilters;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Location;
use App\Models\Stock;
use App\Models\StockMovement;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AdminStatsOverview extends StatsOverviewWidget
{
    use HasCustomPeriodFilters, InteractsWithPageFilters;

    protected string $view = 'filament.admin.widgets.stats-overview';

    protected function getStats(): array
    {
        $period = $this->period();
        $start = $this->periodStart();
        $end = $this->periodEnd();

        $stockMovIn = StockMovement::where('type', 'in');
        $stockMovOut = StockMovement::where('type', 'out');
        $incomeQuery = Income::query();
        $expenseQuery = Expense::query();

        if ($start) {
            $stockMovIn->where('created_at', '>=', $start);
            $stockMovOut->where('created_at', '>=', $start);
            $incomeQuery->where('date', '>=', $start->toDateString());
            $expenseQuery->where('date', '>=', $start->toDateString());
        }
        if ($end) {
            $stockMovIn->where('created_at', '<=', $end);
            $stockMovOut->where('created_at', '<=', $end);
            $incomeQuery->where('date', '<=', $end->toDateString());
            $expenseQuery->where('date', '<=', $end->toDateString());
        }

        $revenue = (float) $incomeQuery->sum('amount');
        $stockIn = (int) $stockMovIn->sum('quantity');
        $stockOut = (int) $stockMovOut->sum(DB::raw('ABS(quantity)'));

        $cogsQuery = StockMovement::where('stock_movements.type', 'out')
            ->join('products', 'products.id', '=', 'stock_movements.product_id');

        if ($start) {
            $cogsQuery->where('stock_movements.created_at', '>=', $start);
        }
        if ($end) {
            $cogsQuery->where('stock_movements.created_at', '<=', $end);
        }

        $cogs = (float) $cogsQuery->sum(DB::raw('ABS(stock_movements.quantity) * products.purchase_price'));

        $purchaseQuery = clone $expenseQuery;
        $purchaseQuery->where('category', 'purchase');
        $totalPurchase = (float) $purchaseQuery->sum('amount');

        $opExpenseQuery = clone $expenseQuery;
        $opExpenseQuery->where('category', '!=', 'purchase');
        $operationalExpense = (float) $opExpenseQuery->sum('amount');

        $profit = $revenue - $cogs - $operationalExpense;

        if ($start && $end) {
            $days = max(1, $start->diffInDays($end) + 1);
            $chartStart = $start->copy();
        } elseif ($start) {
            $days = max(1, $start->diffInDays(now()) + 1);
            $chartStart = $start->copy();
        } else {
            $days = 30; // 30 days for 'all'
            $chartStart = now()->subDays(29)->startOfDay();
        }

        $days = (int) $days;

        $chartValues = collect(range(0, $days - 1))->map(function ($i) use ($chartStart) {
            $day = $chartStart->copy()->addDays($i);

            return (int) Income::where('date', $day->toDateString())->sum('amount');
        })->toArray();

        return [
            Stat::make('Pendapatan', 'Rp '.number_format($revenue, 0, ',', '.'))
                ->description($this->periodLabel())
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($chartValues)
                ->color('success'),
            Stat::make('Untung', 'Rp '.number_format($profit, 0, ',', '.'))
                ->description('Pendapatan - (Modal + Pengeluaran)')
                ->color($profit >= 0 ? 'success' : 'danger'),
            Stat::make('Total Pembelian', 'Rp '.number_format($totalPurchase, 0, ',', '.'))
                ->description($this->periodLabel())
                ->color('warning'),
            Stat::make('Total Pengeluaran', 'Rp '.number_format($operationalExpense, 0, ',', '.'))
                ->description($this->periodLabel())
                ->color('danger'),
            Stat::make('Stok Masuk', number_format($stockIn, 0, ',', '.'))
                ->description('Unit diterima')
                ->color('success'),
            Stat::make('Stok Keluar', number_format($stockOut, 0, ',', '.'))
                ->description('Unit terjual/dikirim')
                ->color('danger'),
            Stat::make('Total Stok', number_format((int) Stock::sum('qty'), 0, ',', '.'))
                ->description('Unit di semua lokasi')
                ->color('warning'),
            Stat::make('Total Aset', 'Rp '.number_format((float) Location::getTotalAssetOfAll(), 0, ',', '.'))
                ->description('Nilai inventaris')
                ->color('primary'),
        ];
    }
}
