<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Sale;
use App\Models\StockEntry;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AdminPaymentMethodChart extends ChartWidget
{
    protected ?string $heading = 'Penjualan per Metode Bayar';

    protected ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $pmExpr = "COALESCE(NULLIF(payment_method, ''), 'other')";

        $salesData = Sale::select(DB::raw("{$pmExpr} as pm"))
            ->selectRaw('count(*) as count')
            ->groupBy(DB::raw($pmExpr))
            ->pluck('count', 'pm');

        $stockOutData = StockEntry::where('type', 'out')
            ->select(DB::raw("{$pmExpr} as pm"))
            ->selectRaw('count(*) as count')
            ->groupBy(DB::raw($pmExpr))
            ->pluck('count', 'pm');

        $data = collect();
        foreach ([$salesData, $stockOutData] as $counts) {
            foreach ($counts as $method => $count) {
                $data->put($method, ($data->get($method, 0)) + $count);
            }
        }

        $colorMap = [
            'cash' => 'rgba(34, 197, 94, 0.8)',
            'transfer' => 'rgba(59, 130, 246, 0.8)',
            'qris' => 'rgba(168, 85, 247, 0.8)',
            'other' => 'rgba(107, 114, 128, 0.8)',
        ];

        $labels = array_map(fn ($m) => match ($m) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer',
            'qris' => 'QRIS',
            'other' => 'Lainnya',
            default => ucfirst($m),
        }, array_keys($data->toArray()));

        $backgroundColors = array_map(
            fn ($m) => $colorMap[$m] ?? 'rgba(156, 163, 175, 0.8)',
            array_keys($data->toArray())
        );

        return [
            'datasets' => [
                [
                    'label' => 'Transaksi',
                    'data' => $data->values()->toArray(),
                    'backgroundColor' => $backgroundColors,
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
