<div x-data="escposPrinter()" x-on:open-printer-modal.window="openModal($event.detail)">
    <x-filament::modal id="printer-scanner-modal" width="md">
        <x-slot name="heading">Hubungkan Printer Bluetooth</x-slot>

        <div class="space-y-4">
            <template x-if="savedPrinter">
                <div class="p-4 bg-primary-50 dark:bg-primary-900/50 rounded-lg flex items-center justify-between border border-primary-200 dark:border-primary-700">
                    <div>
                        <p class="font-medium text-primary-700 dark:text-primary-300">Printer Terhubung:</p>
                        <p class="text-sm font-semibold text-primary-900 dark:text-white" x-text="savedPrinter.name"></p>
                    </div>
                    <x-filament::button size="sm" color="danger" icon="heroicon-o-trash" x-on:click="disconnectPrinter()">
                        Hapus
                    </x-filament::button>
                </div>
            </template>

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
            savedPrinter: null,
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
                this.savedPrinter = JSON.parse(localStorage.getItem('savedPrinter'));
                this.devices = [];
                this.$dispatch('open-modal', { id: 'printer-scanner-modal' });
                await this.startScan();
            },

            disconnectPrinter() {
                localStorage.removeItem('savedPrinter');
                this.savedPrinter = null;
                window.dispatchEvent(new Event('printer-connected'));
                new FilamentNotification().title('Printer dihapus').success().send();
            },

            closeModal() {
                this.stopScan();
                this.$dispatch('close-modal', { id: 'printer-scanner-modal' });
            },

            async startScan() {
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
                        window.dispatchEvent(new Event('printer-connected'));
                        
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
                const ThermalPrinter = window.Capacitor?.Plugins?.CapacitorThermalPrinter;
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
                    const receipt = await response.json();
                    
                    const formatRupiah = (num) => 'Rp ' + new Intl.NumberFormat('id-ID').format(num);

                    // Raw plugin method calls require object arguments
                    await ThermalPrinter.begin();
                    
                    // Header
                    await ThermalPrinter.align({ alignment: 'center' });
                    await ThermalPrinter.bold({ enabled: true });
                    await ThermalPrinter.doubleWidth({ enabled: true });
                    await ThermalPrinter.text({ text: `${receipt.header.storeName}\n` });
                    await ThermalPrinter.clearFormatting();

                    if (receipt.header.address) {
                        await ThermalPrinter.align({ alignment: 'center' });
                        await ThermalPrinter.text({ text: `${receipt.header.address}\n` });
                    }
                    if (receipt.header.phone) {
                        await ThermalPrinter.align({ alignment: 'center' });
                        await ThermalPrinter.text({ text: `${receipt.header.phone}\n` });
                    }

                    await ThermalPrinter.text({ text: "================================\n" });
                    await ThermalPrinter.align({ alignment: 'left' });
                    await ThermalPrinter.text({ text: `No: ${receipt.header.receiptNo} Date: ${receipt.header.date}\n` });
                    if (receipt.header.cashier) {
                        await ThermalPrinter.text({ text: `Cashier: ${receipt.header.cashier}\n` });
                    }
                    await ThermalPrinter.text({ text: "--------------------------------\n" });

                    // Products
                    for (const item of receipt.products) {
                        await ThermalPrinter.text({ text: `${item.name}\n` });
                        const priceStr = formatRupiah(item.price);
                        const total = item.price * item.qty * (1 - (item.discountPct || 0) / 100);
                        const totalStr = formatRupiah(total);

                        let line = `  ${item.qty} ${item.unit} x ${priceStr}`;
                        if (item.discountPct) {
                            line += ` (-${item.discountPct}%)`;
                        }

                        const padding = 32 - line.length - totalStr.length;
                        if (padding > 0) {
                            line += " ".repeat(padding);
                        } else {
                            line += " ";
                        }
                        line += totalStr + "\n";
                        await ThermalPrinter.text({ text: line });
                    }

                    await ThermalPrinter.text({ text: "--------------------------------\n" });

                    // Summary
                    let subtotal = 0;
                    for (const item of receipt.products) {
                        subtotal += item.price * item.qty * (1 - (item.discountPct || 0) / 100);
                    }

                    const padLeft = (label, value) => {
                        const padding = 32 - label.length - value.length;
                        if (padding > 0) {
                            return label + " ".repeat(padding) + value + "\n";
                        }
                        return label + " " + value + "\n";
                    }

                    await ThermalPrinter.text({ text: padLeft("Subtotal:", formatRupiah(subtotal)) });

                    let total = subtotal;

                    if (receipt.globalDiscount) {
                        await ThermalPrinter.text({ text: padLeft("Discount:", "-" + formatRupiah(receipt.globalDiscount)) });
                        total -= receipt.globalDiscount;
                    }

                    if (receipt.taxPercent) {
                        const tax = total * (receipt.taxPercent / 100);
                        await ThermalPrinter.text({ text: padLeft(`Tax (${receipt.taxPercent}%):`, formatRupiah(tax)) });
                        total += tax;
                    }

                    await ThermalPrinter.text({ text: "================================\n" });
                    await ThermalPrinter.bold({ enabled: true });
                    await ThermalPrinter.text({ text: padLeft("TOTAL:", formatRupiah(total)) });
                    await ThermalPrinter.clearFormatting();

                    if (receipt.paymentMethod || receipt.amountPaid !== undefined) {
                        await ThermalPrinter.text({ text: "--------------------------------\n" });
                        if (receipt.paymentMethod) {
                            await ThermalPrinter.text({ text: padLeft("Payment:", receipt.paymentMethod) });
                        }
                        if (receipt.amountPaid !== undefined) {
                            await ThermalPrinter.text({ text: padLeft("Amount Paid:", formatRupiah(receipt.amountPaid)) });
                            const change = receipt.amountPaid - total;
                            await ThermalPrinter.text({ text: padLeft("Change:", formatRupiah(change)) });
                        }
                    }

                    await ThermalPrinter.text({ text: "================================\n" });

                    // Footer
                    await ThermalPrinter.align({ alignment: 'center' });
                    if (receipt.header.note) {
                        await ThermalPrinter.text({ text: `${receipt.header.note}\n` });
                    }
                    await ThermalPrinter.text({ text: "Terima kasih!\nBarang yang sudah dibeli tidak dapat dikembalikan.\n\n\n" });

                    await ThermalPrinter.feedCutPaper({ half: false });
                    await ThermalPrinter.write();
                    
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

        // Handle Capacitor hardware back button
        if (window.Capacitor && window.Capacitor.Plugins.App) {
            window.Capacitor.Plugins.App.addListener('backButton', ({ canGoBack }) => {
                if (canGoBack) {
                    window.history.back();
                } else {
                    window.Capacitor.Plugins.App.exitApp();
                }
            });
        }
    });
</script>
