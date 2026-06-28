<?php

namespace App\Filament\Admin\Pages;

use App\Models\Expense;
use App\Models\Income;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Attributes\Url;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class BookkeepingReport extends Page implements Tables\Contracts\HasTable
{
    use HasResponsiveTable;
    use Tables\Concerns\InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $title = 'Laporan Pembukuan';

    protected string $view = 'filament.admin.pages.bookkeeping-report';

    #[Url(as: 'period')]
    public string $period = 'all';

    #[Url(as: 'tab')]
    public string $activeTab = 'income';

    public function updatedPeriod(): void
    {
        $this->resetTable();
    }

    public function updatedActiveTab(): void
    {
        $this->resetTable();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tipe Pembukuan')
                    ->tabs([
                        'income' => Tab::make('Pendapatan')
                            ->icon(Heroicon::OutlinedArrowDownTray)
                            ->schema([
                                View::make('filament-responsive-table::responsive-table'),
                            ]),
                        'expenses' => Tab::make('Pengeluaran')
                            ->icon(Heroicon::OutlinedArrowUpTray)
                            ->schema([
                                View::make('filament-responsive-table::responsive-table'),
                            ]),
                    ])
                    ->livewireProperty('activeTab')
                    ->columnSpanFull(),
            ]);
    }

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['date', 'location.name', 'category', 'amount'])
            ->cardTitle(fn ($record) => $record->description ?? ucfirst($record->category));
    }

    public function table(Table $table): Table
    {
        $query = $this->activeTab === 'expenses'
            ? Expense::query()->with(['location', 'creator'])
            : Income::query()->with(['location', 'creator']);

        if ($this->period !== 'all') {
            $query->where('date', '>=', $this->getPeriodStart());
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sale' => 'Penjualan',
                        'debt_payment' => 'Pembayaran Hutang',
                        'purchase' => 'Pembelian',
                        'salary' => 'Gaji',
                        'utilities' => 'Utilitas',
                        'transport' => 'Transportasi',
                        'other' => 'Lainnya',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'sale' => 'success',
                        'debt_payment' => 'info',
                        'purchase' => 'warning',
                        'salary' => 'primary',
                        'utilities' => 'info',
                        'transport' => 'gray',
                        'other' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): ?string => match ($state) {
                        'sale' => 'heroicon-o-banknotes',
                        'debt_payment' => 'heroicon-o-receipt-refund',
                        'purchase' => 'heroicon-o-shopping-cart',
                        'salary' => 'heroicon-o-user-group',
                        'utilities' => 'heroicon-o-bolt',
                        'transport' => 'heroicon-o-truck',
                        'other' => 'heroicon-o-folder',
                        default => null,
                    })
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Dibuat oleh'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location_id')
                    ->relationship('location', 'name')
                    ->label('Lokasi')
                    ->preload(),
                Tables\Filters\SelectFilter::make('category')
                    ->options(fn () => $this->activeTab === 'expenses'
                        ? ['purchase' => 'Pembelian', 'salary' => 'Gaji', 'utilities' => 'Utilitas', 'transport' => 'Transportasi', 'other' => 'Lainnya']
                        : ['sale' => 'Penjualan', 'debt_payment' => 'Pembayaran Hutang', 'other' => 'Lainnya']
                    ),
            ])
            ->defaultSort('date', 'desc');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BookkeepingReportStats::class,
        ];
    }

    public function getWidgetData(): array
    {
        return [
            'period' => $this->period,
            'periodStart' => $this->getPeriodStart(),
        ];
    }

    public function getPeriodStart(): ?string
    {
        return match ($this->period) {
            'today' => now()->startOfDay()->toDateString(),
            'week' => now()->subDays(7)->startOfDay()->toDateString(),
            'month' => now()->subDays(30)->startOfDay()->toDateString(),
            default => null,
        };
    }
}
