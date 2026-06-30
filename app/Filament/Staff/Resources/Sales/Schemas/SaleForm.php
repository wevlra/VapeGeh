<?php

namespace App\Filament\Staff\Resources\Sales\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('items')
                    ->label('Item')
                    ->schema([
                        Select::make('product_id')
                            ->label('Produk')
                            ->options(fn () => Product::whereHas('stocks', fn ($q) => $q
                                ->where('location_id', auth()->user()->location_id)
                                ->where('qty', '>', 0),
                            )->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('qty')
                            ->label('Jumlah')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        TextInput::make('price')
                            ->label('Harga')
                            ->numeric()
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->stripCharacters('.')
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->addActionLabel('Tambah item')
                    ->deleteAction(fn ($action) => $action->label('Hapus')),
                Select::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                    ])
                    ->default('cash')
                    ->required(),
                TextInput::make('paid_amount')
                    ->label('Jumlah Dibayar')
                    ->numeric()
                    ->prefix('Rp')
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                    ->stripCharacters('.'),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->placeholder('Catatan untuk transaksi ini...')
                    ->maxLength(1000),
            ]);
    }
}
