<?php

namespace App\Filament\Admin\Resources\Incomes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IncomeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('IDR'),
                DatePicker::make('date')
                    ->required()
                    ->default(now()),
                Textarea::make('description')
                    ->columnSpanFull()
                    ->rows(2),
            ]);
    }
}
