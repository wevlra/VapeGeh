<?php

namespace App\Filament\Admin\Resources\StockTransfers\Pages;

use App\Actions\CompleteStockTransfer;
use App\Filament\Admin\Resources\StockTransfers\StockTransferResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewStockTransfer extends ViewRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('complete')
                ->label('Selesai')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Selesaikan Transfer Stok')
                ->modalDescription(fn ($record): string => "Yakin ingin menyelesaikan transfer {$record->transfer_number}?")
                ->hidden(fn ($record): bool => $record->status !== 'pending')
                ->action(function ($record): void {
                    try {
                        app(CompleteStockTransfer::class)->execute($record, auth()->user());

                        Notification::make()
                            ->title('Transfer selesai')
                            ->body("Transfer \"{$record->transfer_number}\" telah selesai. Stok telah dipindahkan antar lokasi.")
                            ->success()
                            ->send();

                        $this->refreshFormData(['status', 'completed_at']);
                    } catch (\DomainException $e) {
                        Notification::make()
                            ->title('Gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            EditAction::make()
                ->hidden(fn ($record) => $record->status !== 'pending'),
            DeleteAction::make()
                ->hidden(fn ($record) => $record->status !== 'pending'),
        ];
    }
}
