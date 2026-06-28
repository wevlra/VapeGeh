<?php

namespace App\Filament\Admin\Pages;

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

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $title = 'Sales Report';

    protected string $view = 'filament.admin.pages.sales-report';

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
            ->only(['location.name', 'total', 'payment_method'])
            ->cardTitle(fn ($record) => $record->invoice_number);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Sale::query()->with(['user', 'location'])->when(
                $this->period !== 'all',
                fn (Builder $query) => $query->where('created_at', '>=', $this->getPeriodStart()),
            ))
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Cashier')
                    ->searchable(),
                TextColumn::make('total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('payment_method')
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
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location_id')
                    ->relationship('location', 'name')
                    ->label('Location')
                    ->preload(),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                        'other' => 'Other',
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
