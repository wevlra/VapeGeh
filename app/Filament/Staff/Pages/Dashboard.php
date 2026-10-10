<?php

namespace App\Filament\Staff\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Beranda';

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
}
