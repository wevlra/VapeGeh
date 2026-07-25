<?php

namespace App\Filament\Staff\Widgets;

use App\Models\Sale;
use App\Models\StockEntry;
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

        $salesByMonthPos = Sale::where('location_id', $locationId)
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->selectRaw("{$dateExpr}, SUM(total) as total")
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $stockOuts = StockEntry::with('items')->where('type', 'out')
            ->where('location_id', $locationId)
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->get();

        $stockOutByMonth = $stockOuts->groupBy(function ($entry) {
            return $entry->created_at->format('Y-m');
        });

        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));

        $data = $months->map(function (Carbon $month) use ($salesByMonthPos, $stockOutByMonth) {
            $monthKey = $month->format('Y-m');
            $posTotal = (float) ($salesByMonthPos[$monthKey] ?? 0);

            $stockOutTotal = 0;
            if (isset($stockOutByMonth[$monthKey])) {
                foreach ($stockOutByMonth[$monthKey] as $entry) {
                    $itemsTotal = $entry->items->sum(fn ($i) => $i->qty * $i->unit_price);
                    $costs = StockEntry::parseAdditionalCostsTotal($entry->additional_costs);
                    $stockOutTotal += $itemsTotal + $costs;
                }
            }

            return $posTotal + $stockOutTotal;
        });

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
