<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class AdminExpenseCategoryChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Pengeluaran per Kategori';

    protected ?string $pollingInterval = null;

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $start = match ($this->pageFilters['period'] ?? 'all') {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(6)->startOfDay(),
            '30days' => now()->subDays(29)->startOfDay(),
            default => now()->subYear(),
        };

        $data = Expense::where('date', '>=', $start->toDateString())
            ->select('category')
            ->selectRaw('sum(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $colors = [
            'purchase' => 'rgba(234, 179, 8, 0.8)',
            'salary' => 'rgba(59, 130, 246, 0.8)',
            'utilities' => 'rgba(34, 197, 94, 0.8)',
            'transport' => 'rgba(107, 114, 128, 0.8)',
            'other' => 'rgba(168, 85, 247, 0.8)',
        ];

        $labels = array_map(fn ($c) => match ($c) {
            'purchase' => 'Pembelian',
            'salary' => 'Gaji',
            'utilities' => 'Utilitas',
            'transport' => 'Transportasi',
            'other' => 'Lainnya',
            default => ucfirst($c),
        }, array_keys($data->toArray()));

        return [
            'datasets' => [
                [
                    'label' => 'Total (Rp)',
                    'data' => $data->values()->toArray(),
                    'backgroundColor' => array_values($colors),
                    'borderColor' => '#374151',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
