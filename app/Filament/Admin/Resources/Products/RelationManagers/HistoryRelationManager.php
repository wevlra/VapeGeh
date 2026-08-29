<?php

namespace App\Filament\Admin\Resources\Products\RelationManagers;

use App\Filament\Admin\Resources\History\HistoryResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\StockMovement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HistoryRelationManager extends RelationManager
{
    use NotifiesWithDetail;

    protected static string $relationship = 'movements';

    protected static ?string $title = 'Riwayat';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Tanggal')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in' => 'Stok Masuk',
                        'out' => 'Stok Keluar',
                        'transfer' => 'Transfer',
                        'adjustment' => 'Penyesuaian',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'transfer' => 'info',
                        'adjustment' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): ?string => match ($state) {
                        'in' => 'heroicon-o-arrow-down-tray',
                        'out' => 'heroicon-o-arrow-up-tray',
                        'transfer' => 'heroicon-o-arrow-right-end-on-rectangle',
                        'adjustment' => 'heroicon-o-pencil-square',
                        default => null,
                    }),
                TextColumn::make('location.name')
                    ->label('Lokasi'),
                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->formatStateUsing(fn (StockMovement $record): string => $record->quantity > 0 ? '+'.$record->quantity : (string) $record->quantity)
                    ->color(fn (StockMovement $record): string => $record->quantity > 0 ? 'success' : 'danger'),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30)
                    ->placeholder('Tidak ada catatan'),
                TextColumn::make('creator.name')
                    ->label('Oleh'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(
                fn (StockMovement $record): string => HistoryResource::getUrl('view', ['record' => $record])
            );
    }
}
