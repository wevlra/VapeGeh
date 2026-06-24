<?php

namespace App\Filament\Admin\Pages;

use App\Models\Expense;
use App\Models\Income;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
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

        $totalIncome = $incomeQuery->sum('amount');
        $totalExpense = $expenseQuery->sum('amount');
        $netIncome = $totalIncome - $totalExpense;

        $periodLabel = match ($this->period) {
            'today' => 'Today',
            'week' => 'Last 7 Days',
            'month' => 'Last 30 Days',
            default => 'All Time',
        };

        return [
            Stat::make('Total Income', 'Rp '.number_format($totalIncome, 0, ',', '.'))
                ->description($periodLabel)
                ->color('success'),
            Stat::make('Total Expenses', 'Rp '.number_format($totalExpense, 0, ',', '.'))
                ->description($periodLabel)
                ->color('danger'),
            Stat::make('Net Income', 'Rp '.number_format($netIncome, 0, ',', '.'))
                ->description($periodLabel)
                ->color($netIncome >= 0 ? 'success' : 'danger'),
        ];
    }
}
