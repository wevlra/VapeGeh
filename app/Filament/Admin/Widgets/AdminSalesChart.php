<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AdminSalesChart extends ChartWidget
{
    protected ?string $heading = 'Sales Trend';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));

        $data = $months->map(function (Carbon $month) {
            return Sale::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('total');
        });

        $labels = $months->map(fn (Carbon $month) => $month->format('M Y'));

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data->values()->toArray(),
                    'fill' => true,
                ],
            ],
            'labels' => $labels->values()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
