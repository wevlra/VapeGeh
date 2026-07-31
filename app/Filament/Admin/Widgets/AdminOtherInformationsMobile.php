<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Location;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AdminOtherInformationsMobile extends Widget
{
    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.other-informations-mobile';

    public static function canView(): bool
    {
        return true;
    }

    protected function getViewData(): array
    {
        $totalStock = (int) Stock::sum('qty');
        $totalAsset = (float) Location::getTotalAssetOfAll();

        $user = Auth::user();

        $totalProducts = 0;
        $totalStaff = 0;

        if ($user && $user->location_id) {
            $totalProducts = StockMovement::where('location_id', $user->location_id)->distinct('product_id')->count('product_id');
            $totalStaff = User::where('location_id', $user->location_id)->count();
        }

        return [
            'totalStock' => $totalStock,
            'totalAsset' => $totalAsset,
            'totalProducts' => $totalProducts,
            'totalStaff' => $totalStaff,
        ];
    }
}
