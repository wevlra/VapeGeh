<?php

namespace App\Filament\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait HasCustomPeriodFilters
{
    protected function getFilterValue(string $key, $default = null)
    {
        if (property_exists($this, 'filters') && is_array($this->filters)) {
            return $this->filters[$key] ?? $default;
        }
        if (property_exists($this, 'pageFilters') && is_array($this->pageFilters)) {
            return $this->pageFilters[$key] ?? $default;
        }

        return $default;
    }

    protected function period(): string
    {
        return $this->getFilterValue('period', 'all');
    }

    protected function periodStart(): ?Carbon
    {
        return match ($this->period()) {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(6)->startOfDay(),
            '30days' => now()->subDays(29)->startOfDay(),
            '2months' => now()->subMonths(2)->startOfDay(),
            '3months' => now()->subMonths(3)->startOfDay(),
            'custom' => ! empty($this->getFilterValue('start_date')) ? Carbon::parse($this->getFilterValue('start_date'))->startOfDay() : null,
            default => null,
        };
    }

    protected function periodEnd(): ?Carbon
    {
        if ($this->period() === 'custom' && ! empty($this->getFilterValue('end_date'))) {
            return Carbon::parse($this->getFilterValue('end_date'))->endOfDay();
        }

        return null;
    }

    protected function periodLabel(): string
    {
        return match ($this->period()) {
            'today' => 'Hari ini',
            '7days' => '7 hari terakhir',
            '30days' => '30 hari terakhir',
            '2months' => '2 Bulan terakhir',
            '3months' => '3 Bulan terakhir',
            'custom' => 'Kustom ('.
                (! empty($this->getFilterValue('start_date')) ? Carbon::parse($this->getFilterValue('start_date'))->format('d M Y') : '...').
                ' - '.
                (! empty($this->getFilterValue('end_date')) ? Carbon::parse($this->getFilterValue('end_date'))->format('d M Y') : '...').
                ')',
            default => 'Semua waktu',
        };
    }

    protected function applyPeriod(Builder $query, string $column = 'created_at'): Builder
    {
        $start = $this->periodStart();
        $end = $this->periodEnd();

        if ($start) {
            $query->where($column, '>=', $start);
        }
        if ($end) {
            $query->where($column, '<=', $end);
        }

        return $query;
    }
}
