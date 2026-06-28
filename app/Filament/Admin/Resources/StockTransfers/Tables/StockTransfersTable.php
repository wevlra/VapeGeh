<?php

namespace App\Filament\Admin\Resources\StockTransfers\Tables;

use App\Actions\CompleteStockTransfer;
use App\Filament\Admin\Resources\StockTransfers\StockTransferResource;
use App\Models\StockTransfer;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (StockTransfer $record): string => StockTransferResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('transfer_number')
                    ->label('No. Transfer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fromLocation.name')
                    ->label('Dari'),
                TextColumn::make('toLocation.name')
                    ->label('Ke'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Tertunda',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($state),
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'completed' => 'heroicon-o-check-circle',
                        'cancelled' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-minus',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
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
                    ->options([
                        'pending' => 'Tertunda',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->hidden(fn (StockTransfer $record): bool => $record->status !== 'pending'),
                Action::make('complete')
                    ->label('Selesai')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Transfer Stok')
                    ->modalDescription(fn (StockTransfer $record): string => "Yakin ingin menyelesaikan transfer {$record->transfer_number}? Stok akan dikurangi dari sumber dan ditambahkan ke tujuan.")
                    ->hidden(fn (StockTransfer $record): bool => $record->status !== 'pending')
                    ->action(function (StockTransfer $record): void {
                        try {
                            app(CompleteStockTransfer::class)->execute(
                                $record,
                                auth()->user(),
                            );

                            Notification::make()
                                ->title('Transfer selesai')
                                ->body("Transfer \"{$record->transfer_number}\" telah selesai. Stok telah dipindahkan antar lokasi.")
                                ->success()
                                ->send();
                        } catch (\DomainException $e) {
                            Notification::make()
                                ->title('Transfer gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
