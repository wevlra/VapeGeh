<?php

namespace App\Filament\Staff\Widgets;

use App\Models\Income;
use App\Models\Sale;
use App\Models\Stock;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Url;

class StaffStatsOverview extends StatsOverviewWidget
{
    protected string $view = 'filament.admin.widgets.stats-overview';

    #[Url(as: 'period')]
    public string $period = 'all';

    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('period')
                            ->label('Periode')
                            ->options([
                                'all' => 'Semua waktu',
                                'today' => 'Hari ini',
                                '7days' => '7 hari terakhir',
                                '30days' => '30 hari terakhir',
                            ])
                            ->default(fn () => $this->period)
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->setPeriod($state)),
                    ])
                    ->extraAttributes(['class' => 'mb-4'])
                    ->contained(false),

                $this->getSectionContentComponent(),
            ]);
    }

    protected function periodStart(): ?Carbon
    {
        return match ($this->period) {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(6)->startOfDay(),
            '30days' => now()->subDays(29)->startOfDay(),
            default => null,
        };
    }

    protected function periodLabel(): string
    {
        return match ($this->period) {
            'today' => 'Hari ini',
            '7days' => '7 hari terakhir',
            '30days' => '30 hari terakhir',
            default => 'Semua waktu',
        };
    }

    protected function getStats(): array
    {
        $locationId = auth()->user()->location_id;
        $start = $this->periodStart();

        $saleQuery = Sale::where('location_id', $locationId);
        $incomeQuery = Income::where('location_id', $locationId);
        if ($start) {
            $saleQuery->where('created_at', '>=', $start);
            $incomeQuery->where('date', '>=', $start->toDateString());
        }

        $totalSales = $saleQuery->count();
        $totalRevenue = $incomeQuery->sum('amount');
        $totalStock = Stock::where('location_id', $locationId)->sum('qty');

        $days = match ($this->period) {
            'today' => 1,
            '7days' => 7,
            '30days' => 30,
            default => 6,
        };

        $chartStart = $start ?? now()->subMonths($days - 1)->startOfMonth();

        $chartValues = collect(range($days - 1, 0))->map(function ($i) use ($chartStart, $locationId) {
            $day = $chartStart->copy()->addDays($i);

            return (int) Income::where('location_id', $locationId)
                ->where('date', $day->toDateString())
                ->sum('amount');
        })->toArray();

        return [
            Stat::make('Total Penjualan', $totalSales)
                ->description($this->periodLabel())
                ->chart($chartValues)
                ->color('success'),
            Stat::make('Pendapatan', 'Rp '.number_format($totalRevenue, 0, ',', '.'))
                ->description($this->periodLabel())
                ->chart($chartValues)
                ->color('success'),
            Stat::make('Unit Stok', number_format($totalStock))
                ->description('Unit di lokasi Anda')
                ->color('warning'),
        ];
    }
}
