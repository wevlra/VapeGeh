<?php

namespace App\Filament\Staff\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;

class StaffPaymentMethodChart extends ChartWidget
{
    protected ?string $heading = 'Sales by Payment Method';

    protected ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $locationId = auth()->user()->location_id;

        $data = Sale::where('location_id', $locationId)
            ->select('payment_method')
            ->selectRaw('count(*) as count')
            ->groupBy('payment_method')
            ->pluck('count', 'payment_method');

        $colors = [
            'cash' => 'rgba(34, 197, 94, 0.8)',
            'transfer' => 'rgba(59, 130, 246, 0.8)',
            'qris' => 'rgba(168, 85, 247, 0.8)',
        ];

        $labels = array_map(fn ($m) => ucfirst($m), array_keys($data->toArray()));

        return [
            'datasets' => [
                [
                    'label' => 'Transactions',
                    'data' => $data->values()->toArray(),
                    'backgroundColor' => array_values($colors),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
