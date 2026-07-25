<?php

namespace App\Filament\Admin\Resources\StockOpnames\Tables;

use App\Actions\CompleteStockOpname;
use App\Filament\Admin\Resources\StockOpnames\StockOpnameResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\StockOpname;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockOpnamesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (StockOpname $record): string => StockOpnameResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('opname_number')
                    ->label('No. Opname')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->searchable(),

                TextColumn::make('total_difference_value')
                    ->label('Total Selisih Nilai')
                    ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                    ->state(fn (StockOpname $record): float => $record->total_difference_value)
                    ->color(fn (float $state): string => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray'))
                    ->formatStateUsing(function (float $state): string {
                        $prefix = $state > 0 ? '+' : '';

                        return $prefix.'Rp '.number_format($state, 0, ',', '.');
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'completed' => 'Selesai',
                        default => ucfirst($state),
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'draft' => 'heroicon-o-pencil-square',
                        'completed' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-minus',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Dibuat oleh'),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'completed' => 'Selesai',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->hidden(fn (StockOpname $record): bool => $record->status !== 'draft'),
                Action::make('complete')
                    ->label('Selesai')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Stock Opname')
                    ->modalDescription(fn (StockOpname $record): string => "Yakin ingin menyelesaikan opname {$record->opname_number}? Stok di lokasi akan disesuaikan berdasarkan stok fisik.")
                    ->hidden(fn (StockOpname $record): bool => $record->status !== 'draft')
                    ->action(function (StockOpname $record): void {
                        try {
                            app(CompleteStockOpname::class)->execute($record);

                            NotifiesWithDetail::buildSuccessNotification(
                                'Stock Opname Selesai',
                                "Opname \"{$record->opname_number}\" telah selesai."
                            )->send();
                        } catch (DomainException $e) {
                            NotifiesWithDetail::buildErrorNotification(
                                'Gagal Menyelesaikan Stock Opname',
                                $e->getMessage()
                            )->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
