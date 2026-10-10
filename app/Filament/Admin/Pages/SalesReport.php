<?php

namespace App\Filament\Admin\Pages;

use App\Exports\SalesReportExport;
use App\Filament\Concerns\HasExportActions;
use App\Filament\Traits\HasCustomPeriodFilters;
use App\Models\Location;
use App\Models\Sale;
use App\Models\StockEntry;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use UnitEnum;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class SalesReport extends Page implements Tables\Contracts\HasTable
{
    use HasCustomPeriodFilters;
    use HasExportActions;
    use HasFiltersForm, Tables\Concerns\InteractsWithTable {
        HasFiltersForm::normalizeTableFilterValuesFromQueryString insteadof Tables\Concerns\InteractsWithTable;
        HasFiltersForm::updatedFilters as traitUpdatedFilters;
    }
    use HasResponsiveTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $title = 'Laporan Penjualan';

    protected string $view = 'filament.admin.pages.sales-report';

    protected function getHeaderActions(): array
    {
        return [
            $this->getExportActionGroup(
                SalesReportExport::class,
                'exports.pdf.sales-report',
                'laporan-penjualan'
            ),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament-responsive-table::responsive-table'),
            ]);
    }

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['location_name', 'total', 'payment_method'])
            ->cardTitle(fn ($record) => $record->reference ?: 'Stok Keluar');
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
        $salesQ = DB::table('sales')
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->join('locations', 'locations.id', '=', 'sales.location_id')
            ->select([
                DB::raw("'pos' as source_type"),
                'sales.id',
                'sales.invoice_number as reference',
                'locations.name as location_name',
                'users.name as kasir_name',
                'sales.total',
                'sales.payment_method',
                'sales.created_at',
                DB::raw('NULL as additional_costs'),
            ]);

        $stockQ = DB::table('stock_entries')
            ->join('locations', 'locations.id', '=', 'stock_entries.location_id')
            ->join('users', 'users.id', '=', 'stock_entries.created_by')
            ->leftJoinSub(
                DB::table('stock_entry_items')
                    ->select('stock_entry_id', DB::raw('SUM(qty * unit_price) as total'))
                    ->groupBy('stock_entry_id'),
                'totals', fn ($j) => $j->on('totals.stock_entry_id', '=', 'stock_entries.id')
            )
            ->where('stock_entries.type', 'out')
            ->select([
                DB::raw("'stock_out' as source_type"),
                'stock_entries.id',
                DB::raw("'' as reference"),
                'locations.name as location_name',
                'users.name as kasir_name',
                DB::raw('COALESCE(totals.total, 0) as total'),
                'stock_entries.payment_method',
                'stock_entries.created_at',
                'stock_entries.additional_costs',
            ]);

        $union = $salesQ->union($stockQ);

        $model = new Sale;
        $model->setTable('combined');

        return $table
            ->query($model->newQuery()->fromSub($union, 'combined')->when(
                true,
                fn ($query) => $this->applyPeriod($query, 'created_at'),
            ))
            ->columns([
                TextColumn::make('source_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pos' => 'POS',
                        'stock_out' => 'Stok Keluar',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pos' => 'success',
                        'stock_out' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('reference')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('location_name')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kasir_name')
                    ->label('Kasir')
                    ->searchable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(function (float $state, $record): string {
                        $finalTotal = $state + StockEntry::parseAdditionalCostsTotal($record->additional_costs ?? null);

                        return 'Rp '.number_format($finalTotal, 0, ',', '.');
                    })
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'qris' => 'QRIS',
                        'transfer' => 'Transfer',
                        'cash' => 'Tunai',
                        default => $state ? ucfirst($state) : '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                        'qris' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source_type')
                    ->label('Tipe')
                    ->options([
                        'pos' => 'POS',
                        'stock_out' => 'Stok Keluar',
                    ]),
                Tables\Filters\SelectFilter::make('location_name')
                    ->label('Lokasi')
                    ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'name')->all()),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                        'other' => 'Lainnya',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SalesReportStats::class,
        ];
    }

    public function getWidgetData(): array
    {
        return ['pageFilters' => $this->filters ?? ['period' => 'all']];
    }
}
