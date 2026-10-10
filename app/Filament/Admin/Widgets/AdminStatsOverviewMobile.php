<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Traits\HasCustomPeriodFilters;
use App\Models\Expense;
use App\Models\Income;
use App\Models\StockMovement;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class AdminStatsOverviewMobile extends Widget
{
    use HasCustomPeriodFilters, InteractsWithPageFilters;

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.stats-overview-mobile';

    public static function canView(): bool
    {
        return true;
    }

    protected function periodHeading(): string
    {
        return match ($this->period()) {
            'today' => 'Hari Ini',
            '7days' => '7 Hari Terakhir',
            '30days' => '30 Hari Terakhir',
            default => 'Semua Waktu',
        };
    }

    protected function getViewData(): array
    {
        $start = $this->periodStart();
        $end = $this->periodEnd();
        $startDate = $start?->toDateString();
        $endDate = $end?->toDateString();

        $stockMovIn = StockMovement::where('type', 'in');
        $stockMovOut = StockMovement::where('type', 'out');
        $incomeQuery = Income::query();
        $opExpenseQuery = Expense::where('category', '!=', 'purchase');
        $purchaseQuery = Expense::where('category', 'purchase');

        if ($start) {
            $stockMovIn->where('created_at', '>=', $start);
            $stockMovOut->where('created_at', '>=', $start);
            $incomeQuery->where('date', '>=', $startDate);
            $opExpenseQuery->where('date', '>=', $startDate);
            $purchaseQuery->where('date', '>=', $startDate);
        }
        if ($end) {
            $stockMovIn->where('created_at', '<=', $end);
            $stockMovOut->where('created_at', '<=', $end);
            $incomeQuery->where('date', '<=', $endDate);
            $opExpenseQuery->where('date', '<=', $endDate);
            $purchaseQuery->where('date', '<=', $endDate);
        }

        $stockIn = (int) $stockMovIn->sum('quantity');
        $stockOut = (int) $stockMovOut->sum(DB::raw('ABS(quantity)'));

        $revenue = (float) $incomeQuery->sum('amount');

        $cogsQuery = StockMovement::where('type', 'out')
            ->join('products', 'products.id', '=', 'stock_movements.product_id');

        if ($start) {
            $cogsQuery->where('stock_movements.created_at', '>=', $start);
        }
        if ($end) {
            $cogsQuery->where('stock_movements.created_at', '<=', $end);
        }

        $cogs = (float) $cogsQuery->sum(DB::raw('ABS(stock_movements.quantity) * products.purchase_price'));

        $opExpense = (float) $opExpenseQuery->sum('amount');
        $totalPurchase = (float) $purchaseQuery->sum('amount');

        $profit = $revenue - $cogs - $opExpense;

        return [
            'stockIn' => $stockIn,
            'stockOut' => $stockOut,
            'revenue' => $revenue,
            'profit' => $profit,
            'totalPurchase' => $totalPurchase,
            'opExpense' => $opExpense,
            'periodHeading' => $this->periodHeading(),
        ];
    }
}
