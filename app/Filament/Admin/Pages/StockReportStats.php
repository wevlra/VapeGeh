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
        $totalLocations = Location::where('status', 'active')->count();
        $lowStock = Stock::where('qty', '<', 10)->where('qty', '>', 0)->count();

        return [
            Stat::make('Total Unit Stok', number_format($totalStock, 0, ',', '.'))
                ->color('primary'),
            Stat::make('Lokasi Aktif', number_format($totalLocations, 0, ',', '.'))
                ->color('primary'),
            Stat::make('Item Stok Rendah', number_format($lowStock, 0, ',', '.'))
                ->description('Kurang dari 10 unit')
                ->color($lowStock > 0 ? 'danger' : 'success'),
        ];
    }
}
