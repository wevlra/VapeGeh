<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Location;
use App\Models\Stock;
use Filament\Widgets\Widget;

class AdminStockAssetMobile extends Widget
{
    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.stock-asset-mobile';

    public static function canView(): bool
    {
        return true;
    }

    protected function getViewData(): array
    {
        $totalStock = (int) Stock::sum('qty');
        $totalAsset = (float) Location::getTotalAssetOfAll();

        return [
            'totalStock' => $totalStock,
            'totalAsset' => $totalAsset,
        ];
    }
}
