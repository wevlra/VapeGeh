<?php

namespace App\Filament\Admin\Resources\Sales\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('location_id'),
                Repeater::make('items')
                    ->label('Item')
                    ->schema([
                        Select::make('product_id')
                            ->label('Produk')
                            ->options(function (?int $state, callable $get) {
                                $locationId = $get('../../location_id');
                                if (! $locationId) {
                                    return Product::whereHas('stocks', fn ($q) => $q->where('qty', '>', 0))
                                        ->pluck('name', 'id');
                                }

                                return Product::whereHas('stocks', fn ($q) => $q
                                    ->where('location_id', $locationId)
                                    ->where('qty', '>', 0),
                                )->pluck('name', 'id');
                            })
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
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->addActionLabel('Tambah Item')
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
                    ->prefix('Rp'),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->placeholder('Catatan untuk transaksi ini...')
                    ->maxLength(1000),
            ]);
    }
}
