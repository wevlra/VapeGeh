<x-filament-widgets::widget>
    <x-filament::section heading="Stock">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Admin\Pages\StockIn::getUrl() }}"
                color="success"
                icon="heroicon-o-archive-box-arrow-down"
                class="w-full justify-center"
            >
                Stock In
            </x-filament::button>
            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Admin\Pages\StockOut::getUrl() }}"
                color="danger"
                icon="heroicon-o-arrow-up-tray"
                class="w-full justify-center"
            >
                Stock Out
            </x-filament::button>
            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Admin\Resources\StockTransfers\StockTransferResource::getUrl('create') }}"
                color="warning"
                icon="heroicon-o-arrows-right-left"
                class="w-full justify-center"
            >
                Transfer
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
