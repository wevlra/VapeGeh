<?php

namespace App\Filament\Admin\Resources\Expenses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('location_id')
                    ->label('Lokasi')
                    ->relationship('location', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('category')
                    ->label('Kategori')
                    ->required()
                    ->options([
                        'purchase' => 'Pembelian',
                        'salary' => 'Gaji',
                        'utilities' => 'Utilitas',
                        'transport' => 'Transportasi',
                        'other' => 'Lainnya',
                    ]),
                TextInput::make('amount')
                    ->label('Jumlah')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp'),
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->required()
                    ->default(now()),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull()
                    ->rows(2),
            ]);
    }
}
