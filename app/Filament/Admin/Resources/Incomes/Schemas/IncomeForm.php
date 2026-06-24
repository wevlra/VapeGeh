<?php

namespace App\Filament\Admin\Resources\Incomes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class IncomeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('location_id')
                            ->label('Location')
                            ->relationship('location', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('category')
                            ->required()
                            ->options([
                                'sale' => 'Sale',
                                'debt_payment' => 'Debt Payment',
                                'other' => 'Other',
                            ]),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('IDR'),
                        DatePicker::make('date')
                            ->required()
                            ->default(now()),
                    ]),
                Textarea::make('description')
                    ->rows(2),
            ]);
    }
}
