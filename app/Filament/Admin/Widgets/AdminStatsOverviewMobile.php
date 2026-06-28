<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Expense;
use App\Models\Income;
use App\Models\StockMovement;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class AdminStatsOverviewMobile extends Widget
{
    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.stats-overview-mobile';

    public static function canView(): bool
    {
        return true;
    }

    protected function getViewData(): array
    {
        $today = now()->startOfDay();
        $todayDate = $today->toDateString();

        $stockIn = (int) StockMovement::where('type', 'in')
            ->where('created_at', '>=', $today)
            ->sum('quantity');

        $stockOut = (int) StockMovement::where('type', 'out')
            ->where('created_at', '>=', $today)
            ->sum(DB::raw('ABS(quantity)'));

        $income = (float) Income::where('date', '>=', $todayDate)->sum('amount');
        $expense = (float) Expense::where('date', '>=', $todayDate)->sum('amount');
        $revenue = $income;
        $profit = $income - $expense;

        return [
            'stockIn' => $stockIn,
            'stockOut' => $stockOut,
            'revenue' => $revenue,
            'profit' => $profit,
        ];
    }
}
