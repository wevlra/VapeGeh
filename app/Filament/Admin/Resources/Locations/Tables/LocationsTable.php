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
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'store' => 'Toko',
                        'warehouse' => 'Gudang',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'store' => 'primary',
                        'warehouse' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('price_type')
                    ->label('Tipe Harga')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '1' => 'Harga 1',
                        '2' => 'Harga 2',
                        default => "Harga {$state}",
                    })
                    ->color('warning'),
                TextColumn::make('address')
                    ->label('Alamat')
                    ->placeholder('Tidak ada alamat')
                    ->limit(40),
                TextColumn::make('phone')
                    ->label('Telepon')
                    ->placeholder('Tidak ada telepon'),
                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Staf'),
                TextColumn::make('total_asset')
                    ->label('Total Aset')
                    ->getStateUsing(fn (Location $record): string => 'Rp '.number_format((float) $record->total_asset, 0, ',', '.'))
                    ->alignEnd(),
                TextColumn::make('status')
                    ->label('Status')
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
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        default => ucfirst($state),
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
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
