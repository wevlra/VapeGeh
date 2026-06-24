<?php

namespace App\Filament\Staff\Pages;

use App\Models\Sale;
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

        $query = Sale::query()->where('location_id', $locationId);

        if ($this->periodStart) {
            $query->where('created_at', '>=', $this->periodStart);
        }

        $totalSales = $query->count();
        $totalRevenue = $query->sum('total');

        $periodLabel = match ($this->period) {
            'today' => 'Today',
            'week' => 'Last 7 Days',
            'month' => 'Last 30 Days',
            default => 'All Time',
        };

        return [
            Stat::make('Total Sales', number_format($totalSales))
                ->description($periodLabel)
                ->color('success'),
            Stat::make('Total Revenue', 'Rp '.number_format($totalRevenue, 0, ',', '.'))
                ->description($periodLabel)
                ->color('success'),
        ];
    }
}
