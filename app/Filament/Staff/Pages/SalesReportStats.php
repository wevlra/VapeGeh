<?php

namespace App\Filament\Staff\Pages;

use App\Models\Income;
use App\Models\Sale;
use App\Models\StockEntry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class SalesReportStats extends StatsOverviewWidget
{
    #[Reactive]
    public ?string $period = 'all';

    #[Reactive]
    public ?string $periodStart = null;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $locationId = auth()->user()->location_id;

        $saleQuery = Sale::query()->where('location_id', $locationId);
        $stockOutQuery = StockEntry::where('type', 'out')->where('location_id', $locationId);
        $incomeQuery = Income::where('category', 'sale')->where('location_id', $locationId);

        if ($this->periodStart) {
            $saleQuery->where('created_at', '>=', $this->periodStart);
            $stockOutQuery->where('created_at', '>=', $this->periodStart);
            $incomeQuery->where('date', '>=', date('Y-m-d', strtotime($this->periodStart)));
        }

        $totalSales = $saleQuery->count() + $stockOutQuery->count();
        $totalRevenue = $incomeQuery->sum('amount');

        $periodLabel = match ($this->period) {
            'today' => 'Hari Ini',
            'week' => '7 Hari Terakhir',
            'month' => '30 Hari Terakhir',
            default => 'Semua Waktu',
        };

        return [
            Stat::make('Total Penjualan', number_format($totalSales))
                ->description($periodLabel)
                ->color('success'),
            Stat::make('Total Pendapatan', 'Rp '.number_format($totalRevenue, 0, ',', '.'))
                ->description($periodLabel)
                ->color('success'),
        ];
    }
}
