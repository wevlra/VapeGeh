<?php

namespace App\Filament\Admin\Pages;

use App\Models\Location;
use App\Models\Stock;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockReportStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalStock = Stock::sum('qty');
        $totalLocations = Location::where('is_active', true)->count();
        $lowStock = Stock::where('qty', '<', 10)->where('qty', '>', 0)->count();

        return [
            Stat::make('Total Stock Units', number_format($totalStock))
                ->color('primary'),
            Stat::make('Active Locations', $totalLocations)
                ->color('primary'),
            Stat::make('Low Stock Items', $lowStock)
                ->description('Less than 10 units')
                ->color($lowStock > 0 ? 'danger' : 'success'),
        ];
    }
}
