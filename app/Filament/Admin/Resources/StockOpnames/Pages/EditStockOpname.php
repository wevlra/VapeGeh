<?php

namespace App\Filament\Admin\Resources\StockOpnames\Pages;

use App\Actions\CompleteStockOpname;
use App\Filament\Admin\Resources\StockOpnames\StockOpnameResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditStockOpname extends EditRecord
{
    protected static string $resource = StockOpnameResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->status !== 'draft') {
            Notification::make()
                ->warning()
                ->title('Penyesuaian tidak dapat diedit')
                ->body("Penyesuaian \"{$this->record->opname_number}\" sudah selesai dan tidak dapat diedit.")
                ->send();

            $this->redirect(static::getResource()::getUrl('index'));
        }
    }

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

                        $this->redirect($this->getResource()::getUrl('view', ['record' => $record]));
                    } catch (\DomainException $e) {
                        Notification::make()
                            ->title('Gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            DeleteAction::make()
                ->hidden(fn ($record) => $record->status !== 'draft'),
        ];
    }
}
