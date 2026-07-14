<div x-data="receiptActionModal()" x-on:open-receipt-action-modal.window="openModal($event.detail)">
    <x-filament::modal id="receipt-action-modal" width="sm">
        <x-slot name="heading">Nota</x-slot>

        <div class="space-y-3">
            {{-- Opsi Ukuran Kertas --}}
            <div>
                <div class="p-1 rounded-xl bg-gray-100 dark:bg-gray-800 flex w-full">
                    <label class="flex-1 text-center cursor-pointer relative">
                        <input type="radio" x-model="paperSize" value="58mm" class="peer sr-only">
                        <div class="py-2 px-3 text-sm font-medium text-gray-500 dark:text-gray-400 rounded-lg transition peer-checked:bg-white peer-checked:text-primary-600 peer-checked:shadow-sm dark:peer-checked:bg-gray-700 dark:peer-checked:text-primary-400">
                            Ukuran 58mm
                        </div>
                    </label>
                    <label class="flex-1 text-center cursor-pointer relative">
                        <input type="radio" x-model="paperSize" value="80mm" class="peer sr-only">
                        <div class="py-2 px-3 text-sm font-medium text-gray-500 dark:text-gray-400 rounded-lg transition peer-checked:bg-white peer-checked:text-primary-600 peer-checked:shadow-sm dark:peer-checked:bg-gray-700 dark:peer-checked:text-primary-400">
                            Ukuran 80mm
                        </div>
                    </label>
                </div>
            </div>

            {{-- Cetak via Bluetooth --}}
            <button
                type="button"
                x-on:click="printReceipt()"
                x-bind:disabled="isBusy"
                class="w-full flex items-center gap-4 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-600 dark:text-primary-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 15.75v3h10.5v-3M6.75 15.75H4.5a.75.75 0 0 1-.75-.75V8.25A.75.75 0 0 1 4.5 7.5h15a.75.75 0 0 1 .75.75v6.75a.75.75 0 0 1-.75.75h-2.25M6.75 15.75H17.25M6.75 7.5V4.5h10.5V7.5" />
                    </svg>
                </span>
                <div class="text-left flex-1">
                    <p class="font-semibold text-gray-900 dark:text-white text-sm" x-text="printerLabel"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="printerSublabel"></p>
                </div>
                <x-filament::loading-indicator x-show="isBusy && busyAction === 'print'" class="w-5 h-5 text-primary-500" />
            </button>

            {{-- Unduh sebagai Foto --}}
            <button
                type="button"
                x-on:click="downloadAsImage()"
                x-bind:disabled="isBusy"
                class="w-full flex items-center gap-4 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-success-100 dark:bg-success-900/40 text-success-600 dark:text-success-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                </span>
                <div class="text-left flex-1">
                    <p class="font-semibold text-gray-900 dark:text-white text-sm">Unduh</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Simpan nota sebagai gambar</p>
                </div>
                <x-filament::loading-indicator x-show="isBusy && busyAction === 'download'" class="w-5 h-5 text-success-500" />
            </button>

            {{-- Bagikan --}}
            <button
                type="button"
                x-on:click="shareAsImage()"
                x-bind:disabled="isBusy"
                class="w-full flex items-center gap-4 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-info-100 dark:bg-info-900/40 text-info-600 dark:text-info-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                    </svg>
                </span>
                <div class="text-left flex-1">
                    <p class="font-semibold text-gray-900 dark:text-white text-sm">Bagikan</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Bagikan nota sebagai gambar</p>
                </div>
                <x-filament::loading-indicator x-show="isBusy && busyAction === 'share'" class="w-5 h-5 text-info-500" />
            </button>
        </div>

        {{-- Hidden iframe used only when rendering receipt HTML to image --}}
        <iframe
            x-ref="receiptFrame"
            style="position:fixed;top:-9999px;left:-9999px;width:220px;height:1px;border:none;opacity:0;pointer-events:none;"
            aria-hidden="true"
        ></iframe>

        <x-slot name="footerActions">
            <x-filament::button color="gray" x-on:click="closeModal()" x-bind:disabled="isBusy">Tutup</x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('receiptActionModal', () => ({
            jsonUrl: null,
            receiptUrl: null,
            isBusy: false,
            busyAction: null,
            paperSize: localStorage.getItem('savedPaperSize') || '58mm',

            init() {
                this.$watch('paperSize', (val) => {
                    localStorage.setItem('savedPaperSize', val);
                });
            },

            get savedPrinter() {
                try { return JSON.parse(localStorage.getItem('savedPrinter')); } catch { return null; }
            },

            get printerLabel() {
                return this.savedPrinter ? 'Cetak via Bluetooth' : 'Hubungkan Printer';
            },

            get printerSublabel() {
                return this.savedPrinter
                    ? `Printer: ${this.savedPrinter.name}`
                    : 'Belum ada printer yang terhubung';
            },

            openModal(detail) {
                this.jsonUrl = detail.jsonUrl;
                this.receiptUrl = detail.receiptUrl;
                this.isBusy = false;
                this.busyAction = null;
                this.$dispatch('open-modal', { id: 'receipt-action-modal' });
            },

            closeModal() {
                this.$dispatch('close-modal', { id: 'receipt-action-modal' });
            },

            printReceipt() {
                this.closeModal();
                if (this.savedPrinter) {
                    window.dispatchEvent(new CustomEvent('print-native', { detail: { url: this.jsonUrl, paperSize: this.paperSize } }));
                } else {
                    window.dispatchEvent(new CustomEvent('open-printer-modal', { detail: { url: this.jsonUrl, paperSize: this.paperSize } }));
                }
            },

            async downloadAsImage() {
                this.isBusy = true;
                this.busyAction = 'download';
                try {
                    const imageDataUrl = await this._renderReceiptToImage();
                    const filename = 'nota-' + Date.now() + '.png';

                    // Android: Use Capacitor Filesystem to save image
                    if (window.Capacitor?.isNativePlatform?.()) {
                        const { Filesystem } = window.Capacitor.Plugins;
                        if (Filesystem) {
                            const base64Data = imageDataUrl.split(',')[1];
                            await Filesystem.writeFile({
                                path: 'VapeGeh/' + filename,
                                data: base64Data,
                                directory: 'DOCUMENTS',
                                recursive: true
                            });
                            if (typeof FilamentNotification !== 'undefined') {
                                new FilamentNotification().title('Nota berhasil disimpan di folder Documents/VapeGeh').success().send();
                            }
                            return;
                        }
                    }

                    // Web: trigger download via anchor
                    const a = document.createElement('a');
                    a.href = imageDataUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);

                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error('Download failed:', err);
                        if (typeof FilamentNotification !== 'undefined') {
                            new FilamentNotification().title('Gagal mengunduh nota').danger().send();
                        }
                    }
                } finally {
                    this.isBusy = false;
                    this.busyAction = null;
                    this.closeModal();
                }
            },

            async shareAsImage() {
                this.isBusy = true;
                this.busyAction = 'share';
                try {
                    const imageDataUrl = await this._renderReceiptToImage();
                    const filename = 'nota-' + Date.now() + '.png';

                    // Android: Capacitor Share plugin requires file URI, not base64
                    if (window.Capacitor?.isNativePlatform?.()) {
                        const { Share, Filesystem } = window.Capacitor.Plugins;
                        if (Share && Filesystem) {
                            const base64Data = imageDataUrl.split(',')[1];
                            const writeResult = await Filesystem.writeFile({
                                path: filename,
                                data: base64Data,
                                directory: 'CACHE'
                            });
                            await Share.share({
                                title: 'Nota Penjualan',
                                files: [writeResult.uri],
                                dialogTitle: 'Bagikan Nota',
                            });
                            return;
                        }
                    }

                    // Web: Web Share API dengan file
                    const res = await fetch(imageDataUrl);
                    const blob = await res.blob();
                    const file = new File([blob], filename, { type: 'image/png' });

                    if (navigator.canShare?.({ files: [file] })) {
                        await navigator.share({ files: [file], title: 'Nota Penjualan' });
                        return;
                    }

                    // Fallback: salin URL ke clipboard
                    if (window.isSecureContext && navigator.clipboard) {
                        await navigator.clipboard.writeText(this.receiptUrl);
                    }
                    if (typeof FilamentNotification !== 'undefined') {
                        new FilamentNotification()
                            .title('Perangkat tidak mendukung Share. Tautan disalin!')
                            .warning()
                            .send();
                    }

                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error('Share failed:', err);
                        if (typeof FilamentNotification !== 'undefined') {
                            new FilamentNotification().title('Gagal membagikan nota').danger().send();
                        }
                    }
                } finally {
                    this.isBusy = false;
                    this.busyAction = null;
                    this.closeModal();
                }
            },

            /**
             * Load receipt HTML inside a hidden iframe, then use html2canvas
             * (exposed via window.html2canvas from the Vite bundle) to capture
             * the body as a 3x-scale PNG. Receipt width is fixed at 220px (≈58mm @ 96dpi).
             */
            _renderReceiptToImage() {
                return new Promise((resolve, reject) => {
                    const frame = this.$refs.receiptFrame;
                    const widthPx = this.paperSize === '80mm' ? 302 : 220;
                    frame.style.width = widthPx + 'px';

                    const onLoad = async () => {
                        frame.removeEventListener('load', onLoad);
                        try {
                            const doc = frame.contentDocument || frame.contentWindow.document;
                            const body = doc.body;

                            // Wait a tick for layout to settle
                            await new Promise(r => setTimeout(r, 100));

                            const scrollHeight = Math.max(
                                doc.documentElement.scrollHeight,
                                body.scrollHeight,
                                body.offsetHeight,
                                1
                            );
                            frame.style.height = scrollHeight + 'px';

                            const h2c = window.html2canvas;
                            if (!h2c) throw new Error('html2canvas not loaded');

                            const canvas = await h2c(body, {
                                useCORS: true,
                                allowTaint: true,
                                backgroundColor: '#ffffff',
                                scale: 3,
                                width: widthPx,
                                height: scrollHeight,
                                windowWidth: widthPx,
                                windowHeight: scrollHeight,
                                logging: false,
                            });

                            frame.style.height = '1px';
                            resolve(canvas.toDataURL('image/png'));
                        } catch (e) {
                            reject(e);
                        }
                    };

                    frame.addEventListener('load', onLoad);

                    // Append noprint=1 and size
                    const urlObj = new URL(this.receiptUrl, window.location.origin);
                    urlObj.searchParams.set('noprint', '1');
                    urlObj.searchParams.set('size', this.paperSize);
                    frame.src = urlObj.toString();
                });
            },
        }));
    });
</script>
