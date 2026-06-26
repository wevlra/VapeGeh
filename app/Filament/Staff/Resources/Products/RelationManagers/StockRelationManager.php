<?php

namespace App\Filament\Staff\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    public function getTableQuery(): Builder
    {
        $locationId = auth()->user()?->location_id;

        return $this->getRelationship()->query()
            ->where('location_id', $locationId);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('qty')
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
