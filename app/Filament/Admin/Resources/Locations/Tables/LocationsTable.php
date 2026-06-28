<?php

namespace App\Filament\Admin\Resources\Locations\Tables;

use App\Actions\DeleteLocation;
use App\Filament\Admin\Resources\Locations\LocationResource;
use App\Models\Location;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Location $record): string => LocationResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->limit(40),
                TextColumn::make('phone')
                    ->placeholder('—'),
                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Staff'),
                TextColumn::make('total_asset')
                    ->label('Total Asset')
                    ->getStateUsing(fn (Location $record): string => 'Rp '.number_format((float) $record->total_asset, 0, ',', '.'))
                    ->alignEnd(),
                TextColumn::make('status')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'active' => 'heroicon-o-check-circle',
                        'inactive' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-minus',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteLocation::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
