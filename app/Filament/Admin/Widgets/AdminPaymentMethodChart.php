<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;

class AdminPaymentMethodChart extends ChartWidget
{
    protected ?string $heading = 'Penjualan per Metode Bayar';

    protected ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = Sale::select('payment_method')
            ->selectRaw('count(*) as count')
            ->groupBy('payment_method')
            ->pluck('count', 'payment_method');

        $colors = [
            'cash' => 'rgba(34, 197, 94, 0.8)',
            'transfer' => 'rgba(59, 130, 246, 0.8)',
            'qris' => 'rgba(168, 85, 247, 0.8)',
        ];

        $labels = array_map(fn ($m) => match ($m) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer',
            'qris' => 'QRIS',
            default => ucfirst($m),
        }, array_keys($data->toArray()));

        return [
            'datasets' => [
                [
                    'label' => 'Transaksi',
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
