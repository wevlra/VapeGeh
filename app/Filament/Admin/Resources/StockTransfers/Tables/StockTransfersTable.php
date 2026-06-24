<?php

namespace App\Filament\Admin\Resources\StockTransfers\Tables;

use App\Actions\CompleteStockTransfer;
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
            ->columns([
                TextColumn::make('transfer_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fromLocation.name')
                    ->label('From'),
                TextColumn::make('toLocation.name')
                    ->label('To'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
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
                    ->label('Created by'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->hidden(fn (StockTransfer $record): bool => $record->status !== 'pending'),
                Action::make('complete')
                    ->label('Complete')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->modalHeading('Complete Stock Transfer')
                    ->modalDescription(fn (StockTransfer $record): string => "Are you sure you want to complete transfer {$record->transfer_number}? Stock will be deducted from the source and added to the destination.")
                    ->hidden(fn (StockTransfer $record): bool => $record->status !== 'pending')
                    ->action(function (StockTransfer $record): void {
                        try {
                            app(CompleteStockTransfer::class)->execute(
                                $record,
                                auth()->user(),
                            );

                            Notification::make()
                                ->title('Transfer completed successfully')
                                ->body("Transfer {$record->transfer_number} has been completed.")
                                ->success()
                                ->send();
                        } catch (\DomainException $e) {
                            Notification::make()
                                ->title('Transfer failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
