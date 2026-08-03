<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Sale;
use App\Models\StockEntry;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class AdminPaymentMethodChart extends ChartWidget
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
        $start = $this->periodStart();

        $salesQuery = Sale::query();
        $stockOutQuery = StockEntry::where('type', 'out');

        if ($start) {
            $salesQuery->where('created_at', '>=', $start);
            $stockOutQuery->where('created_at', '>=', $start);
        }

        $pmExpr = "COALESCE(NULLIF(payment_method, ''), 'other')";

        $salesData = (clone $salesQuery)
            ->select(DB::raw("{$pmExpr} as pm"))
            ->selectRaw('count(*) as count')
            ->selectRaw('SUM(total) as amount')
            ->groupBy('pm')
            ->get()
            ->keyBy('pm');

        $stockOutData = (clone $stockOutQuery)
            ->withSum('items as items_amount', DB::raw('qty * unit_price'))
            ->get()
            ->groupBy(fn (StockEntry $entry) => $entry->payment_method ?: 'other')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'amount' => (float) $group->sum('items_amount'),
            ]);

        $data = collect();
        foreach ([$salesData, $stockOutData] as $perMethod) {
            foreach ($perMethod as $method => $row) {
                $current = $data->get($method, ['count' => 0, 'amount' => 0]);
                $current['count'] += (int) $row['count'];
                $current['amount'] += (float) $row['amount'];
                $data->put($method, $current);
            }
        }

        $colorMap = [
            'cash' => 'rgba(34, 197, 94, 0.8)',
            'transfer' => 'rgba(59, 130, 246, 0.8)',
            'qris' => 'rgba(168, 85, 247, 0.8)',
            'other' => 'rgba(107, 114, 128, 0.8)',
        ];

        $labels = $data->map(fn ($row, $method) => $this->methodLabel($method, $row))->values()->toArray();

        $backgroundColors = array_map(
            fn ($m) => $colorMap[$m] ?? 'rgba(156, 163, 175, 0.8)',
            array_keys($data->toArray())
        );

        return [
            'datasets' => [
                [
                    'label' => 'Transaksi',
                    'data' => $data->map(fn ($row) => $row['count'])->values()->toArray(),
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
            'other' => 'Lainnya',
            default => ucfirst($method),
        };

        return $name.' · Rp '.number_format((float) $row['amount'], 0, ',', '.');
    }
}
