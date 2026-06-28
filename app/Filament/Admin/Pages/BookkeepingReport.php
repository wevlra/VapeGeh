<?php

namespace App\Filament\Admin\Pages;

use App\Models\Expense;
use App\Models\Income;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Attributes\Url;

class BookkeepingReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $title = 'Bookkeeping Report';

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
                Tabs::make('Bookkeeping Type')
                    ->tabs([
                        'income' => Tab::make('Income')
                            ->icon(Heroicon::OutlinedArrowDownTray)
                            ->schema([
                                EmbeddedTable::make(),
                            ]),
                        'expenses' => Tab::make('Expenses')
                            ->icon(Heroicon::OutlinedArrowUpTray)
                            ->schema([
                                EmbeddedTable::make(),
                            ]),
                    ])
                    ->livewireProperty('activeTab')
                    ->columnSpanFull(),
            ]);
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
                    ->date()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'debt_payment' => 'Debt Payment',
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
                    ->searchable()
                    ->limit(40),
                TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Created by'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location_id')
                    ->relationship('location', 'name')
                    ->label('Location')
                    ->preload(),
                Tables\Filters\SelectFilter::make('category')
                    ->options(fn () => $this->activeTab === 'expenses'
                        ? ['purchase' => 'Purchase', 'salary' => 'Salary', 'utilities' => 'Utilities', 'transport' => 'Transport', 'other' => 'Other']
                        : ['sale' => 'Sale', 'debt_payment' => 'Debt Payment', 'other' => 'Other']
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
