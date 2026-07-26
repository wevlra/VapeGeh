<?php

namespace App\Filament\Admin\Pages;

use App\Models\Expense;
use App\Models\Income;
use App\Models\StockMovement;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class BookkeepingReportStats extends StatsOverviewWidget
{
    #[Reactive]
    public ?string $period = 'all';

    #[Reactive]
    public ?string $periodStart = null;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $incomeQuery = Income::query();
        $expenseQuery = Expense::query();

        if ($this->periodStart) {
            $incomeQuery->where('date', '>=', $this->periodStart);
            $expenseQuery->where('date', '>=', $this->periodStart);
        }

        $totalIncome = (float) $incomeQuery->sum('amount');
        $totalExpense = (float) $expenseQuery->sum('amount');

        $cogsQuery = StockMovement::where('type', 'out')->join('products', 'products.id', '=', 'stock_movements.product_id');
        if ($this->periodStart) {
            $cogsQuery->where('stock_movements.created_at', '>=', $this->periodStart);
        }
        $cogs = (float) $cogsQuery->sum(DB::raw('ABS(stock_movements.quantity) * products.purchase_price'));

        $opExpenseQuery = clone $expenseQuery;
        $opExpenseQuery->where('category', '!=', 'purchase');
        $opExpense = (float) $opExpenseQuery->sum('amount');

        $netIncome = $totalIncome - $cogs - $opExpense;

        $periodLabel = match ($this->period) {
            'today' => 'Hari Ini',
            'week' => '7 Hari Terakhir',
            'month' => '30 Hari Terakhir',
            default => 'Semua Waktu',
        };

        return [
            Stat::make('Total Pendapatan', 'Rp '.number_format($totalIncome, 0, ',', '.'))
                ->description($periodLabel)
                ->color('success'),
            Stat::make('Total Pengeluaran', 'Rp '.number_format($totalExpense, 0, ',', '.'))
                ->description($periodLabel)
                ->color('danger'),
            Stat::make('Untung', 'Rp '.number_format($netIncome, 0, ',', '.'))
                ->description('Pendapatan - (Modal + Pengeluaran)')
                ->color($netIncome >= 0 ? 'success' : 'danger'),
        ];
    }
}
