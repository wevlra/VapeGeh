<?php

namespace App\Filament\Admin\Resources\Stocks\Widgets;

use App\Models\Location;
use App\Models\Stock;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WarehouseStockStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $warehouseId = Location::where('type', 'warehouse')->value('id');

        if (! $warehouseId) {
            return [
                Stat::make('Total Products', '0')
                    ->description('No warehouse found')
                    ->color('gray'),
            ];
        }

        $lowStock = Stock::where('location_id', $warehouseId)->where('qty', '>', 0)->where('qty', '<', 10)->count();
        $outOfStock = Stock::where('location_id', $warehouseId)->where('qty', '=', 0)->count();

        return [
            Stat::make('Low Stock Items', $lowStock)
                ->description('Less than 10 units')
                ->color($lowStock > 0 ? 'warning' : 'success'),
            Stat::make('Out of Stock', $outOfStock)
                ->description('Need restock')
                ->color($outOfStock > 0 ? 'danger' : 'success'),
        ];
    }
}
