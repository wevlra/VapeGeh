<?php

namespace App\Filament\Staff\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    public function table(Table $table): Table
    {
        $locationId = auth()->user()?->location_id;

        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('location_id', $locationId))
            ->columns([
                TextColumn::make('location.name')
                    ->label('Location'),
                TextColumn::make('qty')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    }),
            ])
            ->recordActions([]);
    }
}
