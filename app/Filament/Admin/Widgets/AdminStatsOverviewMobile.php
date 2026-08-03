<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Expense;
use App\Models\Income;
use App\Models\StockMovement;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class AdminStatsOverviewMobile extends Widget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.stats-overview-mobile';

    public static function canView(): bool
    {
        return true;
    }

    protected function period(): string
    {
        return $this->pageFilters['period'] ?? 'all';
    }

    protected function periodStart(): ?Carbon
    {
        return match ($this->period()) {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(6)->startOfDay(),
            '30days' => now()->subDays(29)->startOfDay(),
            default => null,
        };
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
        $startDate = $start?->toDateString();

        $stockMovIn = StockMovement::where('type', 'in');
        $stockMovOut = StockMovement::where('type', 'out');
        $incomeQuery = Income::query();
        $opExpenseQuery = Expense::where('category', '!=', 'purchase');

        if ($start) {
            $stockMovIn->where('created_at', '>=', $start);
            $stockMovOut->where('created_at', '>=', $start);
            $incomeQuery->where('date', '>=', $startDate);
            $opExpenseQuery->where('date', '>=', $startDate);
        }

        $stockIn = (int) $stockMovIn->sum('quantity');
        $stockOut = (int) $stockMovOut->sum(DB::raw('ABS(quantity)'));

        $revenue = (float) $incomeQuery->sum('amount');

        $cogsQuery = StockMovement::where('type', 'out')
            ->join('products', 'products.id', '=', 'stock_movements.product_id');

        if ($start) {
            $cogsQuery->where('stock_movements.created_at', '>=', $start);
        }

        $cogs = (float) $cogsQuery->sum(DB::raw('ABS(stock_movements.quantity) * products.purchase_price'));

        $opExpense = (float) $opExpenseQuery->sum('amount');

        $profit = $revenue - $cogs - $opExpense;

        return [
            'stockIn' => $stockIn,
            'stockOut' => $stockOut,
            'revenue' => $revenue,
            'profit' => $profit,
            'periodHeading' => $this->periodHeading(),
        ];
    }
}
