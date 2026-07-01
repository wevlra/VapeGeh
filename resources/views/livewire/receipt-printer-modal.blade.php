<div>
    <x-filament::modal id="receipt-printer-modal" width="md">
        <x-slot name="heading">Cetak Nota — Pilih Printer</x-slot>

        @if ($isTauri)
            <div x-data="printerModal()" class="space-y-4">
                <!-- Tab: Pilih tipe koneksi -->
                <x-filament::tabs x-model="connectionType">
                    <x-filament::tabs.item value="bluetooth">
                        Bluetooth
                    </x-filament::tabs.item>
                    <x-filament::tabs.item value="usb">
                        USB
                    </x-filament::tabs.item>
                </x-filament::tabs>

                <!-- Dropdown printer -->
                <x-filament::input.wrapper>
                    <x-filament::input.select x-model="selectedPrinter">
                        <option value="">— Pilih printer —</option>
                        <template x-for="p in filteredPrinters" :key="p.address">
                            <option :value="p.address" x-text="`${p.name} (${p.connection_type})`"></option>
                        </template>
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                <!-- Tombol aksi -->
                <div class="flex gap-x-3">
                    <x-filament::button
                        color="gray"
                        @click="loadPrinters()"
                        x-bind:disabled="loading"
                    >
                        <span x-show="!loading">Cari Printer</span>
                        <span x-show="loading" x-cloak>Memuat…</span>
                    </x-filament::button>

                    <x-filament::button
                        color="primary"
                        @click="print()"
                        x-bind:disabled="loading || !selectedPrinter"
                    >
                        <span x-show="!loading">Cetak</span>
                        <span x-show="loading" x-cloak>Mencetak…</span>
                    </x-filament::button>
                </div>

                <div x-show="loading" class="text-sm text-gray-500">
                    <span x-text="selectedPrinter ? 'Mencetak...' : 'Mencari printer...'"></span>
                </div>
            </div>
        @else
            <p class="text-sm text-gray-500">
                Cetak nota hanya tersedia di aplikasi desktop.
            </p>

            <x-slot name="footerActions">
                <x-filament::button x-on:click="close" color="gray">
                    Tutup
                </x-filament::button>
            </x-slot>
        @endif
    </x-filament::modal>
</div>
