<?php

namespace App\Filament\Admin\Resources\Expenses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ExpenseForm
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
                                'purchase' => 'Purchase',
                                'salary' => 'Salary',
                                'utilities' => 'Utilities',
                                'transport' => 'Transport',
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
