<?php

namespace App\Filament\Staff\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StaffSalesChart extends ChartWidget
{
    protected ?string $heading = 'Tren Penjualan';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $locationId = auth()->user()->location_id;
        $start = now()->subMonths(5)->startOfMonth();
        $end = now()->endOfMonth();

        $dateExpr = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at) as month_key"
            : "DATE_FORMAT(created_at, '%Y-%m') as month_key";

        $salesByMonth = Sale::where('location_id', $locationId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("{$dateExpr}, SUM(total) as total")
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));
        $data = $months->map(fn (Carbon $month) => (float) ($salesByMonth[$month->format('Y-m')] ?? 0));
        $labels = $months->map(fn (Carbon $month) => $month->format('M Y'));

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan',
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
