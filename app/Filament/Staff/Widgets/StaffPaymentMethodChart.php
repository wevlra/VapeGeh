<?php

namespace App\Filament\Staff\Widgets;

use App\Models\Sale;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class StaffPaymentMethodChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Penjualan per Metode Bayar';

    protected ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    protected function periodStart(): ?Carbon
    {
        return match ($this->pageFilters['period'] ?? 'all') {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(6)->startOfDay(),
            '30days' => now()->subDays(29)->startOfDay(),
            default => null,
        };
    }

    protected function getData(): array
    {
        $locationId = auth()->user()->location_id;
        $start = $this->periodStart();

        $pmExpr = "COALESCE(NULLIF(payment_method, ''), 'other')";

        $salesQuery = Sale::where('location_id', $locationId);

        if ($start) {
            $salesQuery->where('created_at', '>=', $start);
        }

        $data = $salesQuery
            ->select(DB::raw("{$pmExpr} as pm"))
            ->selectRaw('count(*) as count')
            ->selectRaw('SUM(total) as amount')
            ->groupBy('pm')
            ->get()
            ->keyBy('pm');

        $colors = [
            'cash' => 'rgba(34, 197, 94, 0.8)',
            'transfer' => 'rgba(59, 130, 246, 0.8)',
            'qris' => 'rgba(168, 85, 247, 0.8)',
        ];

        $labels = $data->map(fn ($row, $method) => $this->methodLabel($method, $row))->values()->toArray();

        $backgroundColors = array_map(
            fn ($m) => $colors[$m] ?? 'rgba(156, 163, 175, 0.8)',
            array_keys($data->toArray())
        );

        return [
            'datasets' => [
                [
                    'label' => 'Transaksi',
                    'data' => $data->map(fn ($row) => (int) $row['count'])->values()->toArray(),
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

    private function methodLabel(string $method, mixed $row): string
    {
        $name = match ($method) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer',
            'qris' => 'QRIS',
            default => ucfirst($method),
        };

        return $name.' · Rp '.number_format((float) $row['amount'], 0, ',', '.');
    }
}
