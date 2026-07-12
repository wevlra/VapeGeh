<x-filament-panels::page>
    <x-filament-actions::modals />
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex gap-3">
            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Admin\Resources\StockTransfers\StockTransferResource::getUrl('index') }}"
                color="gray"
            >
                Batal
            </x-filament::button>
            <x-filament::button type="submit">
                Simpan Perubahan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
