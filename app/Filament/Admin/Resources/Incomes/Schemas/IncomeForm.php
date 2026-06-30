<?php

namespace App\Filament\Admin\Resources\Incomes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class IncomeForm
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
                        'sale' => 'Penjualan',
                        'debt_payment' => 'Pembayaran Hutang',
                        'other' => 'Lainnya',
                    ]),
                TextInput::make('amount')
                    ->label('Jumlah')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp')
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                    ->stripCharacters('.'),
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
