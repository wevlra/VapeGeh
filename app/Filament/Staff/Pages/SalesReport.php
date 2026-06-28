<?php

namespace App\Filament\Staff\Pages;

use App\Models\Sale;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class SalesReport extends Page implements Tables\Contracts\HasTable
{
    use HasResponsiveTable;
    use Tables\Concerns\InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

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
            ->cardTitle(fn ($record) => $record->invoice_number);
    }

    public function table(Table $table): Table
    {
        $locationId = auth()->user()->location_id;

        return $table
            ->query(Sale::query()->where('location_id', $locationId)->with('user')->when(
                $this->period !== 'all',
                fn (Builder $query) => $query->where('created_at', '>=', $this->getPeriodStart()),
            ))
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->searchable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->label('Jumlah Dibayar')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'qris' => 'QRIS',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                        'qris' => 'primary',
                        'other' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
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
