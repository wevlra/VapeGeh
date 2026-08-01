<?php

namespace App\Filament\Staff\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class StaffPaymentMethodChart extends ChartWidget
{
    protected ?string $heading = 'Penjualan per Metode Bayar';

    protected ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $locationId = auth()->user()->location_id;

        $pmExpr = "COALESCE(NULLIF(payment_method, ''), 'other')";

        $data = Sale::where('location_id', $locationId)
            ->select(DB::raw("{$pmExpr} as pm"))
            ->selectRaw('count(*) as count')
            ->groupBy('pm')
            ->pluck('count', 'pm');

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
