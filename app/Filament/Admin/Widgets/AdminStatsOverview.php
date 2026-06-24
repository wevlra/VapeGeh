<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalProducts = Product::where('is_active', true)->count();
        $totalLocations = Location::where('is_active', true)->count();
        $totalSales = Sale::count();
        $totalRevenue = Sale::sum('total');
        $totalStock = Stock::sum('qty');
        $totalIncome = Income::sum('amount');
        $totalExpense = Expense::sum('amount');

        return [
            Stat::make('Active Products', $totalProducts)
                ->description('Total active products')
                ->color('primary'),
            Stat::make('Locations', $totalLocations)
                ->description('Active locations')
                ->color('primary'),
            Stat::make('Total Sales', $totalSales)
                ->description('All-time sales count')
                ->chart(collect(range(6, 0))->map(fn ($i) => Sale::whereBetween('created_at', [
                    now()->subMonths($i)->startOfMonth(),
                    now()->subMonths($i)->endOfMonth(),
                ])->count())->toArray())
                ->color('success'),
            Stat::make('Revenue', 'Rp '.number_format($totalRevenue, 0, ',', '.'))
                ->description('All-time revenue')
                ->chart(collect(range(6, 0))->map(fn ($i) => (int) Sale::whereBetween('created_at', [
                    now()->subMonths($i)->startOfMonth(),
                    now()->subMonths($i)->endOfMonth(),
                ])->sum('total'))->toArray())
                ->color('success'),
            Stat::make('Total Stock', number_format($totalStock))
                ->description('Units across all locations')
                ->color('warning'),
            Stat::make('Net Income', 'Rp '.number_format($totalIncome - $totalExpense, 0, ',', '.'))
                ->description('Income - Expenses')
                ->color($totalIncome >= $totalExpense ? 'success' : 'danger'),
        ];
    }
}
