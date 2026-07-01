<?php

namespace App\Livewire;

use Filament\Notifications\Notification;
use Livewire\Component;

class ReceiptPrinterModal extends Component
{
    public int $movementId = 0;

    public array $printers = [];

    public ?string $selectedPrinter = null;

    public string $connectionType = 'bluetooth';

    public bool $printing = false;

    public bool $isTauri = false;

    public function mount(): void
    {
        $this->isTauri = session('__tauri', false);
    }

    public function initReceiptPrint(int $movementId): void
    {
        $this->movementId = $movementId;
        $this->printers = [];
        $this->selectedPrinter = null;
        $this->connectionType = 'bluetooth';
        $this->printing = false;
        $this->dispatch('open-modal', id: 'receipt-printer-modal');
    }

    public function loadPrinters(): void
    {
        // Called from Alpine in blade — dispatch JS call via Livewire
        $this->dispatch('load-printers');
    }

    #[On('load-printers')]
    public function loadPrintersCallback(array $printers): void
    {
        $this->printers = $printers;

        if (empty($printers)) {
            Notification::make()
                ->title('Tidak ada printer Bluetooth ditemukan')
                ->body('Pasangkan printer Bluetooth melalui Pengaturan Sistem terlebih dahulu.')
                ->warning()
                ->send();
        }
    }

    public function print(): void
    {
        if (! $this->selectedPrinter) {
            Notification::make()
                ->title('Pilih printer terlebih dahulu')
                ->warning()
                ->send();

            return;
        }

        $this->printing = true;
        // Dispatch JS event — Alpine picks it up and calls bridge
        $this->dispatch('print-receipt-tauri', movementId: $this->movementId, printerName: $this->selectedPrinter);
    }

    public function printSuccess(): void
    {
        $this->printing = false;
        $this->dispatch('close-modal', id: 'receipt-printer-modal');

        Notification::make()
            ->title('Nota berhasil dicetak')
            ->success()
            ->send();
    }

    public function printError(array $message): void
    {
        $this->printing = false;

        $text = $message['message'] ?? 'Terjadi kesalahan saat mencetak.';

        $title = match (true) {
            str_contains($text, 'NotFound') => 'Printer tidak ditemukan',
            str_contains($text, 'Connection') => 'Gagal terhubung ke printer',
            str_contains($text, 'NoPrinters') => 'Tidak ada printer terdeteksi',
            default => 'Gagal mencetak nota',
        };

        Notification::make()
            ->title($title)
            ->body($text)
            ->danger()
            ->send();
    }

    public function render()
    {
        return view('livewire.receipt-printer-modal', [
            'isTauri' => $this->isTauri,
            'printers' => $this->printers,
            'selectedPrinter' => $this->selectedPrinter,
            'connectionType' => $this->connectionType,
            'printing' => $this->printing,
        ]);
    }
}
