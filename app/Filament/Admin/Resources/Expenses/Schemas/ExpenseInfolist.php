<?php

namespace App\Filament\Admin\Resources\Expenses\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpenseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Expense Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('category')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => ucfirst($state))
                            ->color(fn (string $state): string => match ($state) {
                                'purchase' => 'warning',
                                'salary' => 'primary',
                                'utilities' => 'info',
                                'transport' => 'gray',
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
