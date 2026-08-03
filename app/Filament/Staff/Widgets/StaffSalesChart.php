<?php

namespace App\Filament\Staff\Widgets;

use App\Models\Sale;
use App\Models\StockEntry;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StaffSalesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Tren Penjualan';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $period = $this->pageFilters['period'] ?? 'all';
        $locationId = auth()->user()->location_id;

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        $config = match ($period) {
            'today' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
                'points' => collect(range(0, 23))->map(fn ($hour) => now()->startOfDay()->addHours($hour)),
                'expr' => $isSqlite ? "strftime('%Y-%m-%d %H', created_at)" : "DATE_FORMAT(created_at, '%Y-%m-%d %H')",
                'key' => fn (Carbon $point) => $point->format('Y-m-d H'),
                'label' => fn (Carbon $point) => $point->format('H:00'),
            ],
            '7days' => $this->dailyConfig(6, $isSqlite),
            '30days' => $this->dailyConfig(29, $isSqlite),
            default => [
                'start' => now()->subMonths(5)->startOfMonth(),
                'end' => now()->endOfMonth(),
                'points' => collect(range(5, 0))->map(fn ($month) => now()->subMonths($month)),
                'expr' => $isSqlite ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')",
                'key' => fn (Carbon $point) => $point->format('Y-m'),
                'label' => fn (Carbon $point) => $point->format('M Y'),
            ],
        };

        $salesByBucket = Sale::where('location_id', $locationId)
            ->whereBetween('created_at', [$config['start']->toDateTimeString(), $config['end']->toDateTimeString()])
            ->selectRaw("{$config['expr']} as bucket_key, SUM(total) as total")
            ->groupBy('bucket_key')
            ->pluck('total', 'bucket_key');

        $stockOuts = StockEntry::with('items')->where('type', 'out')
            ->where('location_id', $locationId)
            ->whereBetween('created_at', [$config['start']->toDateTimeString(), $config['end']->toDateTimeString()])
            ->get();

        $stockOutByBucket = $stockOuts->groupBy(fn (StockEntry $entry) => $config['key']($entry->created_at));

        $data = $config['points']->map(function (Carbon $point) use ($salesByBucket, $stockOutByBucket, $config) {
            $bucketKey = $config['key']($point);
            $posTotal = (float) ($salesByBucket[$bucketKey] ?? 0);

            $stockOutTotal = 0;
            if (isset($stockOutByBucket[$bucketKey])) {
                foreach ($stockOutByBucket[$bucketKey] as $entry) {
                    $itemsTotal = $entry->items->sum(fn ($item) => $item->qty * $item->unit_price);
                    $costs = StockEntry::parseAdditionalCostsTotal($entry->additional_costs);
                    $stockOutTotal += $itemsTotal + $costs;
                }
            }

            return $posTotal + $stockOutTotal;
        });

        $labels = $config['points']->map($config['label']);

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

    /**
     * @return array{start: Carbon, end: Carbon, points: Collection<int, Carbon>, expr: string, key: callable, label: callable}
     */
    private function dailyConfig(int $daysAgo, bool $isSqlite): array
    {
        return [
            'start' => now()->subDays($daysAgo)->startOfDay(),
            'end' => now()->endOfDay(),
            'points' => collect(range($daysAgo, 0))->map(fn ($day) => now()->subDays($day)),
            'expr' => $isSqlite ? "strftime('%Y-%m-%d', created_at)" : "DATE_FORMAT(created_at, '%Y-%m-%d')",
            'key' => fn (Carbon $point) => $point->format('Y-m-d'),
            'label' => fn (Carbon $point) => $point->format('d M'),
        ];
    }
}
