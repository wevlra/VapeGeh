<?php

namespace App\Filament\Admin\Pages;

use App\Exports\BookkeepingExport;
use App\Filament\Concerns\HasExportActions;
use App\Filament\Traits\HasCustomPeriodFilters;
use App\Models\Expense;
use App\Models\Income;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
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
use UnitEnum;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class BookkeepingReport extends Page implements Tables\Contracts\HasTable
{
    use HasCustomPeriodFilters;
    use HasExportActions;
    use HasFiltersForm, Tables\Concerns\InteractsWithTable {
        HasFiltersForm::normalizeTableFilterValuesFromQueryString insteadof Tables\Concerns\InteractsWithTable;
        HasFiltersForm::updatedFilters as traitUpdatedFilters;
    }
    use HasResponsiveTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $title = 'Laporan Pembukuan';

    protected string $view = 'filament.admin.pages.bookkeeping-report';

    #[Url(as: 'tab')]
    public string $activeTab = 'income';

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

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('period')
                    ->label('Periode')
                    ->options([
                        'all' => 'Semua waktu',
                        'today' => 'Hari ini',
                        '7days' => '7 hari terakhir',
                        '30days' => '30 hari terakhir',
                        '2months' => '2 Bulan terakhir',
                        '3months' => '3 Bulan terakhir',
                        'custom' => 'Kustom',
                    ])
                    ->default('today')
                    ->native(false)
                    ->columnSpanFull()
                    ->live(),
                DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->visible(fn (callable $get) => $get('period') === 'custom')
                    ->required(fn (callable $get) => $get('period') === 'custom')
                    ->maxDate(now()),
                DatePicker::make('end_date')
                    ->label('Tanggal Akhir')
                    ->visible(fn (callable $get) => $get('period') === 'custom')
                    ->required(fn (callable $get) => $get('period') === 'custom')
                    ->maxDate(now()),
            ])->columns(3);
    }

    public function updatedFilters(): void
    {
        $this->traitUpdatedFilters();
        $this->resetTable();
    }

    public function updated($name): void
    {
        if (str_starts_with($name, 'filters')) {
            $this->resetTable();
        }
    }

    public function table(Table $table): Table
    {
        $query = $this->activeTab === 'expenses'
            ? Expense::query()->with(['location', 'creator'])
            : Income::query()->with(['location', 'creator']);

        $this->applyPeriod($query, 'date');

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
                        'service' => 'Jasa / Service',
                        'cashback' => 'Cashback & Rebate',
                        'opname' => 'Penyesuaian Stok',
                        'investment' => 'Modal / Investasi',
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
                        'service' => 'primary',
                        'cashback' => 'warning',
                        'opname' => 'gray',
                        'investment' => 'success',
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
                        'service' => 'heroicon-o-wrench-screwdriver',
                        'cashback' => 'heroicon-o-gift',
                        'opname' => 'heroicon-o-pencil-square',
                        'investment' => 'heroicon-o-currency-dollar',
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
                    ->limit(40)
                    ->placeholder('Tidak ada deskripsi'),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
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
                    ->label('Kategori')
                    ->options(fn () => $this->activeTab === 'expenses'
                        ? ['purchase' => 'Pembelian', 'salary' => 'Gaji', 'utilities' => 'Utilitas', 'transport' => 'Transportasi', 'other' => 'Lainnya']
                        : ['sale' => 'Penjualan', 'debt_payment' => 'Pembayaran Hutang', 'service' => 'Jasa / Service', 'cashback' => 'Cashback & Rebate', 'opname' => 'Penyesuaian Stok', 'investment' => 'Modal / Investasi', 'other' => 'Lainnya']
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
        return ['pageFilters' => $this->filters ?? ['period' => 'all']];
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getExportActionGroup(
                BookkeepingExport::class,
                'exports.pdf.bookkeeping',
                'laporan-pembukuan-'.$this->activeTab
            ),
        ];
    }

    public function getExportInstance(string $exportClass, $query)
    {
        return new $exportClass($query, $this->activeTab);
    }

    public function buildExportQuery()
    {
        $query = $this->activeTab === 'expenses'
            ? Expense::query()->with(['location', 'creator'])
            : Income::query()->with(['location', 'creator']);

        $this->applyPeriod($query, 'date');

        $locationId = $this->getTableFilterState('location_id')['value'] ?? null;
        $category = $this->getTableFilterState('category')['value'] ?? null;

        if (filled($locationId)) {
            $query->where('location_id', $locationId);
        }
        if (filled($category)) {
            $query->where('category', $category);
        }

        $sort = $this->getTableSortColumn() ?? 'date';
        $dir = $this->getTableSortDirection() ?? 'desc';
        $query->orderBy($sort, $dir);

        return $query;
    }

    public function getExportTitle(): string
    {
        return $this->activeTab === 'income' ? 'Laporan Pendapatan' : 'Laporan Pengeluaran';
    }

    public function getFilterInfo(): string
    {
        return 'Periode: '.$this->periodLabel();
    }
}
