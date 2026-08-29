<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Income;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminSalesChart extends ChartWidget
{
    use InteractsWithPageFilters;

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
        return match ($period) {
            'today' => [
                'column' => 'created_at',
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
                'column' => 'date',
                'start' => now()->subMonths(5)->startOfMonth(),
                'end' => now()->endOfMonth(),
                'points' => collect(range(5, 0))->map(fn ($month) => now()->subMonths($month)),
                'expr' => $isSqlite ? "strftime('%Y-%m', date)" : "DATE_FORMAT(date, '%Y-%m')",
                'key' => fn (Carbon $point) => $point->format('Y-m'),
                'label' => fn (Carbon $point) => $point->format('M Y'),
            ],
        };
    }

    /**
     * @return array{column: string, start: Carbon, end: Carbon, points: Collection<int, Carbon>, expr: string, key: callable, label: callable}
     */
    private function dailyConfig(int $daysAgo, bool $isSqlite): array
    {
        return [
            'column' => 'date',
            'start' => now()->subDays($daysAgo)->startOfDay(),
            'end' => now()->endOfDay(),
            'points' => collect(range($daysAgo, 0))->map(fn ($day) => now()->subDays($day)),
            'expr' => $isSqlite ? "strftime('%Y-%m-%d', date)" : "DATE_FORMAT(date, '%Y-%m-%d')",
            'key' => fn (Carbon $point) => $point->format('Y-m-d'),
            'label' => fn (Carbon $point) => $point->format('d M'),
        ];
    }
}
