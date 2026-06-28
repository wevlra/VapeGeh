<?php

namespace App\Filament\Staff\Widgets;

use App\Models\Sale;
use App\Models\Stock;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StaffStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $locationId = auth()->user()->location_id;

        $totalSales = Sale::where('location_id', $locationId)->count();
        $totalRevenue = Sale::where('location_id', $locationId)->sum('total');
        $totalStock = Stock::where('location_id', $locationId)->sum('qty');

        return [
            Stat::make('Total Sales', $totalSales)
                ->description('Your location sales')
                ->chart(collect(range(6, 0))->map(fn ($i) => Sale::where('location_id', $locationId)
                    ->whereBetween('created_at', [
                        now()->subMonths($i)->startOfMonth(),
                        now()->subMonths($i)->endOfMonth(),
                    ])->count())->toArray())
                ->color('success'),
            Stat::make('Revenue', 'Rp '.number_format($totalRevenue, 0, ',', '.'))
                ->description('Your location revenue')
                ->chart(collect(range(6, 0))->map(fn ($i) => (int) Sale::where('location_id', $locationId)
                    ->whereBetween('created_at', [
                        now()->subMonths($i)->startOfMonth(),
                        now()->subMonths($i)->endOfMonth(),
                    ])->sum('total'))->toArray())
                ->color('success'),
            Stat::make('Stock Units', number_format($totalStock))
                ->description('Units at your location')
                ->color('warning'),
        ];
    }
}
