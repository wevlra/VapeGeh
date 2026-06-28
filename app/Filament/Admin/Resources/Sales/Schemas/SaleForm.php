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
                    ->label('Items')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
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
                            ->label('Qty')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->addActionLabel('Add item')
                    ->deleteAction(fn ($action) => $action->label('Remove')),
                Select::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash' => 'Cash',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                    ])
                    ->default('cash')
                    ->required(),
                TextInput::make('paid_amount')
                    ->label('Paid Amount')
                    ->numeric()
                    ->prefix('Rp'),
                Textarea::make('notes')
                    ->label('Notes')
                    ->placeholder('Notes for this transaction...')
                    ->maxLength(1000),
            ]);
    }
}
