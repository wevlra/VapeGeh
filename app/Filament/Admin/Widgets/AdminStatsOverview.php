<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Location;
use App\Models\Stock;
use App\Models\StockMovement;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

class AdminStatsOverview extends StatsOverviewWidget
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
                            ->afterStateUpdated(fn ($state) => $this->setPeriod($state ?? 'all')),
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
        $start = $this->periodStart();

        $stockMovIn = StockMovement::where('type', 'in');
        $stockMovOut = StockMovement::where('type', 'out');
        $incomeQuery = Income::query();
        $expenseQuery = Expense::query();

        if ($start) {
            $stockMovIn->where('created_at', '>=', $start);
            $stockMovOut->where('created_at', '>=', $start);
            $incomeQuery->where('date', '>=', $start->toDateString());
            $expenseQuery->where('date', '>=', $start->toDateString());
        }

        $revenue = (float) $incomeQuery->sum('amount');
        $stockIn = (int) $stockMovIn->sum('quantity');
        $stockOut = (int) $stockMovOut->sum(DB::raw('ABS(quantity)'));

        $cogsQuery = StockMovement::where('type', 'out');

        if ($start) {
            $cogsQuery->where('created_at', '>=', $start);
        }

        $cogs = (float) $cogsQuery->sum(DB::raw('ABS(quantity) * COALESCE(unit_price, 0)'));

        $opExpenseQuery = clone $expenseQuery;
        $opExpenseQuery->where('category', '!=', 'purchase');
        $operationalExpense = (float) $opExpenseQuery->sum('amount');

        $profit = $revenue - $cogs - $operationalExpense;

        $days = match ($this->period) {
            'today' => 1,
            '7days' => 7,
            '30days' => 30,
            default => 6,
        };

        $chartStart = $start ?? now()->subMonths($days - 1)->startOfMonth();

        $chartValues = collect(range($days - 1, 0))->map(function ($i) use ($chartStart) {
            $day = $chartStart->copy()->addDays($i);

            return (int) Income::where('date', $day->toDateString())->sum('amount');
        })->toArray();

        return [
            Stat::make('Pendapatan', 'Rp '.number_format($revenue, 0, ',', '.'))
                ->description($this->periodLabel())
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($chartValues)
                ->color('success'),
            Stat::make('Untung', 'Rp '.number_format($profit, 0, ',', '.'))
                ->description('Pendapatan - (Modal + Pengeluaran)')
                ->color($profit >= 0 ? 'success' : 'danger'),
            Stat::make('Stok Masuk', number_format($stockIn, 0, ',', '.'))
                ->description('Unit diterima')
                ->color('success'),
            Stat::make('Stok Keluar', number_format($stockOut, 0, ',', '.'))
                ->description('Unit terjual/dikirim')
                ->color('danger'),
            Stat::make('Total Stok', number_format((int) Stock::sum('qty'), 0, ',', '.'))
                ->description('Unit di semua lokasi')
                ->color('warning'),
            Stat::make('Total Aset', 'Rp '.number_format((float) Location::getTotalAssetOfAll(), 0, ',', '.'))
                ->description('Nilai inventaris')
                ->color('primary'),
        ];
    }
}
