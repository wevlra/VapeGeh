<?php

namespace App\Filament\Staff\Pages;

use App\Models\Sale;
use App\Models\StockEntry;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use UnitEnum;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class SalesReport extends Page implements Tables\Contracts\HasTable
{
    use HasResponsiveTable;
    use Tables\Concerns\InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $title = 'Laporan Penjualan';

    protected string $view = 'filament.staff.pages.sales-report';

    #[Url]
    public string $period = 'all';

    public function updatedPeriod(): void
    {
        $this->resetTable();
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
            ->only(['total', 'payment_method'])
            ->cardTitle(fn ($record) => $record->reference ?: 'Stok Keluar');
    }

    public function table(Table $table): Table
    {
        $locationId = auth()->user()->location_id;

        $salesQ = DB::table('sales')
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->join('locations', 'locations.id', '=', 'sales.location_id')
            ->where('sales.location_id', $locationId)
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
            ->where('stock_entries.location_id', $locationId)
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
                $this->period !== 'all',
                fn ($query) => $query->where('created_at', '>=', $this->getPeriodStart()),
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
        return [
            'period' => $this->period,
            'periodStart' => $this->getPeriodStart(),
        ];
    }

    public function getPeriodStart(): ?string
    {
        return match ($this->period) {
            'today' => now()->startOfDay()->toDateTimeString(),
            'week' => now()->subDays(7)->startOfDay()->toDateTimeString(),
            'month' => now()->subDays(30)->startOfDay()->toDateTimeString(),
            default => null,
        };
    }
}
