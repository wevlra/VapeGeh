<?php

namespace App\Filament\Staff\Pages;

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
                    ])
                    ->default('today')
                    ->native(false)
                    ->columnSpanFull(),
            ]);
    }
}
