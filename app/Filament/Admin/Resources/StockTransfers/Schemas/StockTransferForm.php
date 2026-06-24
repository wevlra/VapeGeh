<?php

namespace App\Filament\Admin\Resources\StockTransfers\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class StockTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make()
                    ->columnSpanFull()
                    ->steps([
                        Step::make('Location')
                            ->description('Select source and destination')
                            ->icon(Heroicon::OutlinedMapPin)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('from_location_id')
                                            ->label('From Location')
                                            ->relationship('fromLocation', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload(),
                                        Select::make('to_location_id')
                                            ->label('To Location')
                                            ->relationship('toLocation', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->different('from_location_id'),
                                    ]),
                                Textarea::make('notes')
                                    ->rows(2),
                            ]),
                        Step::make('Line Items')
                            ->description('Select products and quantities')
                            ->icon(Heroicon::OutlinedCube)
                            ->schema([
                                Repeater::make('items')
                                    ->relationship()
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->addActionLabel('Add Product')
                                    ->table([
                                        TableColumn::make('Product')
                                            ->width('65%'),
                                        TableColumn::make('Quantity')
                                            ->width('30%'),
                                    ])
                                    ->schema([
                                        Select::make('product_id')
                                            ->relationship('product', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload(),

                                        TextInput::make('qty')
                                            ->label('Quantity')
                                            ->integer()
                                            ->minValue(1)
                                            ->maxValue(2147483647)
                                            ->default(1)
                                            ->required(),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
