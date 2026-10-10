<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Traits\HasCustomPeriodFilters;
use App\Models\Income;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminSalesChart extends ChartWidget
{
    use HasCustomPeriodFilters, InteractsWithPageFilters;

    protected ?string $heading = 'Tren Penjualan';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $period = $this->pageFilters['period'] ?? 'all';

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        $config = $this->trendConfig($period, $isSqlite);

        $bounds = $config['column'] === 'date'
            ? [$config['start']->toDateString(), $config['end']->toDateString()]
            : [$config['start']->toDateTimeString(), $config['end']->toDateTimeString()];

        $revenueByBucket = Income::whereBetween($config['column'], $bounds)
            ->selectRaw("{$config['expr']} as bucket_key, SUM(amount) as total")
            ->groupBy('bucket_key')
            ->pluck('total', 'bucket_key');

        $data = $config['points']->map(fn (Carbon $point) => (float) ($revenueByBucket[$config['key']($point)] ?? 0));

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
     * @return array{column: string, start: Carbon, end: Carbon, points: Collection<int, Carbon>, expr: string, key: callable, label: callable}
     */
    private function trendConfig(string $period, bool $isSqlite): array
    {
        $start = $this->periodStart();
        $end = $this->periodEnd() ?? now()->endOfDay();

        if (! $start) {
            // "all" time: show last 6 months
            $start = now()->subMonths(5)->startOfMonth();
            $end = now()->endOfMonth();
        }

        $diffDays = $start->diffInDays($end);

        if ($diffDays <= 1) {
            // Hourly
            $pointsCount = max(1, $start->diffInHours($end));

            return [
                'column' => 'created_at',
                'start' => $start,
                'end' => $end,
                'points' => collect(range(0, $pointsCount))->map(fn ($hour) => $start->copy()->addHours($hour)),
                'expr' => $isSqlite ? "strftime('%Y-%m-%d %H', created_at)" : "DATE_FORMAT(created_at, '%Y-%m-%d %H')",
                'key' => fn (Carbon $point) => $point->format('Y-m-d H'),
                'label' => fn (Carbon $point) => $point->format('H:00'),
            ];
        } elseif ($diffDays <= 90) {
            // Daily
            return [
                'column' => 'date',
                'start' => $start,
                'end' => $end,
                'points' => collect(range(0, $diffDays))->map(fn ($day) => $start->copy()->addDays($day)),
                'expr' => $isSqlite ? "strftime('%Y-%m-%d', date)" : "DATE_FORMAT(date, '%Y-%m-%d')",
                'key' => fn (Carbon $point) => $point->format('Y-m-d'),
                'label' => fn (Carbon $point) => $point->format('d M'),
            ];
        } else {
            // Monthly
            $diffMonths = $start->diffInMonths($end);

            return [
                'column' => 'date',
                'start' => $start->copy()->startOfMonth(),
                'end' => $end->copy()->endOfMonth(),
                'points' => collect(range(0, $diffMonths))->map(fn ($month) => $start->copy()->addMonths($month)->startOfMonth()),
                'expr' => $isSqlite ? "strftime('%Y-%m', date)" : "DATE_FORMAT(date, '%Y-%m')",
                'key' => fn (Carbon $point) => $point->format('Y-m'),
                'label' => fn (Carbon $point) => $point->format('M Y'),
            ];
        }
    }
}
