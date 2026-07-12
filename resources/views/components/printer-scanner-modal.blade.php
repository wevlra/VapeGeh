<div x-data="escposPrinter()" x-on:open-printer-modal.window="openModal($event.detail)">
    <x-filament::modal id="printer-scanner-modal" width="md">
        <x-slot name="heading">Hubungkan Printer Bluetooth</x-slot>

        <div class="space-y-4">
            <template x-if="isScanning">
                <div class="flex items-center justify-center space-x-2 text-primary-500">
                    <x-filament::loading-indicator class="w-6 h-6" />
                    <span>Mencari perangkat...</span>
                </div>
            </template>

            <template x-if="!isScanning && devices.length === 0">
                <p class="text-gray-500 text-center">Tidak ada perangkat Bluetooth ditemukan.</p>
            </template>

            <ul class="space-y-2">
                <template x-for="device in devices" :key="device.address">
                    <li class="p-3 border rounded-lg flex justify-between items-center bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                        <div>
                            <p class="font-medium" x-text="device.name || 'Unknown Device'"></p>
                            <p class="text-xs text-gray-500" x-text="device.address"></p>
                        </div>
                        <x-filament::button size="sm" x-on:click="connectToDevice(device)" x-bind:disabled="isConnecting">
                            Connect
                        </x-filament::button>
                    </li>
                </template>
            </ul>
        </div>

        <x-slot name="footerActions">
            <x-filament::button color="gray" x-on:click="closeModal()">Batal</x-filament::button>
            <x-filament::button x-on:click="startScan()" x-bind:disabled="isScanning">
                <span x-text="isScanning ? 'Mencari...' : 'Scan Ulang'"></span>
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('escposPrinter', () => ({
            devices: [],
            isScanning: false,
            isConnecting: false,
            pendingReceipt: null,
            listeners: [],

            init() {
                window.addEventListener('print-native', (e) => {
                    this.printReceiptData(e.detail.url);
                });
            },

            async openModal(receiptData) {
                this.pendingReceipt = receiptData;
                this.devices = [];
                this.$dispatch('open-modal', { id: 'printer-scanner-modal' });
                await this.startScan();
            },

            closeModal() {
                this.stopScan();
                this.$dispatch('close-modal', { id: 'printer-scanner-modal' });
            },

            async startScan() {
                if (!window.Capacitor || !window.Capacitor.isNativePlatform()) return;
                
                this.isScanning = true;
                this.devices = [];
                
                const ThermalPrinter = window.Capacitor.Plugins.CapacitorThermalPrinter;
                if (!ThermalPrinter) return;
                
                const discoverListener = await ThermalPrinter.addListener('discoverDevices', (data) => {
                    this.devices = data.devices;
                });
                const finishListener = await ThermalPrinter.addListener('discoveryFinish', () => {
                    this.isScanning = false;
                });
                this.listeners.push(discoverListener, finishListener);
                
                try {
                    await ThermalPrinter.startScan();
                } catch (e) {
                    console.error(e);
                    this.isScanning = false;
                }
            },

            async stopScan() {
                if (this.isScanning) {
                    try {
                        const ThermalPrinter = window.Capacitor.Plugins.CapacitorThermalPrinter;
                        if (ThermalPrinter) await ThermalPrinter.stopScan();
                    } catch (e) {}
                }
                this.listeners.forEach(l => l.remove());
                this.listeners = [];
            },

            async connectToDevice(device) {
                const ThermalPrinter = window.Capacitor.Plugins.CapacitorThermalPrinter;
                if (!ThermalPrinter) return;

                this.isConnecting = true;
                try {
                    const result = await ThermalPrinter.connect({ address: device.address });
                    if (result) {
                        localStorage.setItem('savedPrinter', JSON.stringify(result));
                        
                        new FilamentNotification()
                            .title('Printer terhubung!')
                            .success()
                            .send();
                            
                        this.closeModal();
                        
                        if (this.pendingReceipt) {
                            this.printReceiptData(this.pendingReceipt.url);
                        }
                    }
                } catch (e) {
                    new FilamentNotification()
                        .title('Gagal menghubungkan printer')
                        .danger()
                        .send();
                } finally {
                    this.isConnecting = false;
                }
            },

            async printReceiptData(url) {
                const ThermalPrinter = window.Capacitor.Plugins.CapacitorThermalPrinter;
                if (!ThermalPrinter) return;

                try {
                    // Coba connect ulang jika perlu
                    const savedPrinter = JSON.parse(localStorage.getItem('savedPrinter'));
                    if (savedPrinter) {
                        try {
                            await ThermalPrinter.connect({ address: savedPrinter.address });
                        } catch (err) {
                            console.error('Reconnect error', err);
                        }
                    }

                    const response = await fetch(url);
                    if (!response.ok) throw new Error('Gagal mengambil data nota');
                    const textData = await response.text();

                    await ThermalPrinter.printFormattedText({ text: textData });
                    
                    new FilamentNotification()
                        .title('Mencetak nota...')
                        .success()
                        .send();
                } catch(e) {
                     console.error("Print fail", e);
                     new FilamentNotification()
                        .title('Gagal mencetak nota')
                        .danger()
                        .send();
                }
            }
        }));
    });
</script>
