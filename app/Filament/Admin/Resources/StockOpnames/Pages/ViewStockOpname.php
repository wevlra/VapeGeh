<?php

namespace App\Filament\Admin\Resources\StockOpnames\Pages;

use App\Actions\CompleteStockOpname;
use App\Filament\Admin\Resources\StockOpnames\StockOpnameResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewStockOpname extends ViewRecord
{
    protected static string $resource = StockOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('complete')
                ->label('Selesai')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Selesaikan Stock Opname')
                ->modalDescription(fn ($record): string => "Yakin ingin menyelesaikan opname {$record->opname_number}? Stok di lokasi akan disesuaikan berdasarkan stok fisik.")
                ->hidden(fn ($record) => $record->status !== 'draft')
                ->action(function ($record): void {
                    try {
                        app(CompleteStockOpname::class)->execute($record);

                        Notification::make()
                            ->title('Stock Opname selesai')
                            ->body("Opname \"{$record->opname_number}\" telah selesai.")
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
                ->hidden(fn ($record) => $record->status !== 'draft'),
            DeleteAction::make()
                ->hidden(fn ($record) => $record->status !== 'draft'),
        ];
    }
}
