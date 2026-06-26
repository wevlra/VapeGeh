<?php

namespace App\Filament\Admin\Resources\Incomes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IncomeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Income Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('category')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'debt_payment' => 'Debt Payment',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'sale' => 'success',
                                'debt_payment' => 'info',
                                'other' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('amount')
                            ->money('IDR'),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                        TextEntry::make('location.name')
                            ->label('Location'),
                        TextEntry::make('creator.name')
                            ->label('Created by'),
                        TextEntry::make('date')
                            ->date(),
                    ]),
            ]);
    }
}
