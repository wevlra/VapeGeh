<?php

namespace App\Filament\Admin\Resources\StockOpnames\Pages;

use App\Actions\CompleteStockOpname;
use App\Filament\Admin\Resources\StockOpnames\StockOpnameResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStockOpname extends EditRecord
{
    use NotifiesWithDetail;

    protected static string $resource = StockOpnameResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->status !== 'draft') {
            $this->notifyWarning(
                'Penyesuaian Tidak Dapat Diedit',
                "Penyesuaian \"{$this->record->opname_number}\" sudah selesai dan tidak dapat diedit."
            );

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

                        $this->notifySuccess(
                            'Stock Opname Selesai',
                            "Opname \"{$record->opname_number}\" telah selesai."
                        );

                        $this->redirect($this->getResource()::getUrl('view', ['record' => $record]));
                    } catch (\DomainException $e) {
                        $this->notifyError(
                            'Gagal Menyelesaikan Stock Opname',
                            $e->getMessage()
                        );
                    }
                }),
            DeleteAction::make()
                ->hidden(fn ($record) => $record->status !== 'draft'),
        ];
    }
}
