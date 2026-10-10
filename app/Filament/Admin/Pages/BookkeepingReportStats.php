<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Traits\HasCustomPeriodFilters;
use App\Models\Expense;
use App\Models\Income;
use App\Models\StockMovement;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class BookkeepingReportStats extends StatsOverviewWidget
{
    use HasCustomPeriodFilters;
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $start = $this->periodStart();
        $end = $this->periodEnd();

        $incomeQuery = Income::query();
        $expenseQuery = Expense::query();

        if ($start) {
            $incomeQuery->where('date', '>=', $start);
            $expenseQuery->where('date', '>=', $start);
        }
        if ($end) {
            $incomeQuery->where('date', '<=', $end);
            $expenseQuery->where('date', '<=', $end);
        }

        $totalIncome = (float) $incomeQuery->sum('amount');
        $purchaseQuery = clone $expenseQuery;
        $purchaseQuery->where('category', 'purchase');
        $totalPurchase = (float) $purchaseQuery->sum('amount');

        $cogsQuery = StockMovement::where('stock_movements.type', 'out')
            ->join('products', 'products.id', '=', 'stock_movements.product_id');
        if ($start) {
            $cogsQuery->where('stock_movements.created_at', '>=', $start);
        }
        if ($end) {
            $cogsQuery->where('stock_movements.created_at', '<=', $end);
        }
        $cogs = (float) $cogsQuery->sum(DB::raw('ABS(stock_movements.quantity) * products.purchase_price'));

        $opExpenseQuery = Expense::query();
        if ($start) {
            $opExpenseQuery->where('date', '>=', $start);
        }
        if ($end) {
            $opExpenseQuery->where('date', '<=', $end);
        }
        $opExpenseQuery->where('category', '!=', 'purchase');
        $opExpense = (float) $opExpenseQuery->sum('amount');

        $netIncome = $totalIncome - $cogs - $opExpense;

        $periodLabel = $this->periodLabel();

        return [
            Stat::make('Total Pendapatan', 'Rp '.number_format($totalIncome, 0, ',', '.'))
                ->description($periodLabel)
                ->color('success'),
            Stat::make('Total Pembelian', 'Rp '.number_format($totalPurchase, 0, ',', '.'))
                ->description($periodLabel)
                ->color('warning'),
            Stat::make('Total Pengeluaran', 'Rp '.number_format($opExpense, 0, ',', '.'))
                ->description($periodLabel)
                ->color('danger'),
            Stat::make('Untung', 'Rp '.number_format($netIncome, 0, ',', '.'))
                ->description('Pendapatan - (Modal + Pengeluaran)')
                ->color($netIncome >= 0 ? 'success' : 'danger'),
        ];
    }
}
