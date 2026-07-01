// Tauri v2 bridge
document.addEventListener('livewire:init', () => {
    if (typeof window.__TAURI_INTERNALS__ === 'undefined') return;

    const tauriInvoke = (cmd, args) => window.__TAURI_INTERNALS__.invoke(cmd, args || {});

    window.__TAURI_BRIDGE__ = {
        async listPrinters() {
            return await tauriInvoke('plugin:printer|list_printers');
        },
        async printReceipt(movementId, address, connectionType) {
            const r = await fetch(`/api/receipt/${movementId}`);
            if (!r.ok) throw new Error(`API ${r.status}`);
            await tauriInvoke('plugin:printer|print_receipt', {
                address,
                connectionType,
                receiptData: await r.json()
            });
        },
    };

    window.__TAURI_DATA = { movementId: null };

    window.addEventListener('print-receipt-init', e => {
        window.__TAURI_DATA.movementId = e.detail.movementId;
        const m = Livewire.all().find(c => c.name === 'receipt-printer-modal');
        if (m) m.$wire.initReceiptPrint(e.detail.movementId);
    });

    Livewire.hook('component.init', ({ component }) => {
        if ('isTauri' in (component.snapshot?.data || {})) {
            component.$wire.$set('isTauri', true, false);
        }
    });
});

// Global Alpine printer modal component — USB + Bluetooth
window.printerModal = function() {
    return {
        printers: [],
        selectedPrinter: '',
        connectionType: 'bluetooth',
        loading: false,

        get filteredPrinters() {
            return this.printers.filter(p => p.connection_type === this.connectionType);
        },

        async loadPrinters() {
            this.loading = true;
            try {
                this.printers = await window.__TAURI_BRIDGE__.listPrinters();
            } catch(e) {
                this.$wire.printError({ message: String(e) });
            }
            this.loading = false;
        },

        async print() {
            if (!this.selectedPrinter) {
                this.$wire.printError({ message: 'Pilih printer terlebih dahulu' });
                return;
            }
            this.loading = true;
            try {
                await window.__TAURI_BRIDGE__.printReceipt(
                    window.__TAURI_DATA.movementId,
                    this.selectedPrinter,
                    this.connectionType
                );
                this.$wire.printSuccess();
            } catch(e) {
                this.$wire.printError({ message: String(e) });
            }
            this.loading = false;
        }
    };
};
