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

    #[Reactive]
    public array $dateFilter = [];

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $incomeQuery = Income::query();
        $expenseQuery = Expense::query();
        $cogsQuery = StockMovement::where('stock_movements.type', 'out')
            ->join('products', 'products.id', '=', 'stock_movements.product_id');
        $opExpenseQuery = Expense::where('category', '!=', 'purchase');

        $from = $this->dateFilter['from'] ?? null;
        $until = $this->dateFilter['until'] ?? null;

        if (filled($from) || filled($until)) {
            if (filled($from)) {
                $incomeQuery->whereDate('date', '>=', $from);
                $expenseQuery->whereDate('date', '>=', $from);
                $cogsQuery->whereDate('stock_movements.created_at', '>=', $from);
                $opExpenseQuery->whereDate('date', '>=', $from);
            }
            if (filled($until)) {
                $incomeQuery->whereDate('date', '<=', $until);
                $expenseQuery->whereDate('date', '<=', $until);
                $cogsQuery->whereDate('stock_movements.created_at', '<=', $until);
                $opExpenseQuery->whereDate('date', '<=', $until);
            }
        } elseif ($this->periodStart) {
            $incomeQuery->where('date', '>=', $this->periodStart);
            $expenseQuery->where('date', '>=', $this->periodStart);
            $cogsQuery->where('stock_movements.created_at', '>=', $this->periodStart);
            $opExpenseQuery->where('date', '>=', $this->periodStart);
        }

        $totalIncome = (float) $incomeQuery->sum('amount');
        $totalExpense = (float) $expenseQuery->sum('amount');
        $cogs = (float) $cogsQuery->sum(DB::raw('ABS(stock_movements.quantity) * products.purchase_price'));
        $opExpense = (float) $opExpenseQuery->sum('amount');

        $netIncome = $totalIncome - $cogs - $opExpense;

        $periodLabel = match ($this->period) {
            'today' => 'Hari Ini',
            'week' => '7 Hari Terakhir',
            'month' => '30 Hari Terakhir',
            default => 'Semua Waktu',
        };

        $stats = [
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

        $locations = \App\Models\Location::all();
        foreach ($locations as $location) {
            $locIncomeQuery = clone $incomeQuery;
            $locExpenseQuery = clone $expenseQuery;
            
            $locIncome = (float) $locIncomeQuery->where('location_id', $location->id)->sum('amount');
            $locExpense = (float) $locExpenseQuery->where('location_id', $location->id)->sum('amount');
            
            if ($locIncome > 0 || $locExpense > 0) {
                $stats[] = Stat::make("Pendapatan {$location->name}", 'Rp '.number_format($locIncome, 0, ',', '.'))
                    ->description('Pengeluaran: Rp '.number_format($locExpense, 0, ',', '.'))
                    ->color('info');
            }
        }

        return $stats;
    }
}
