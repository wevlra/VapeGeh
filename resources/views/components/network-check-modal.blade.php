<div
    x-data="networkCheck()"
    x-init="initNetworkCheck"
    x-show="!isConnected && hasChecked"
    x-cloak
    x-transition.opacity.duration.300ms
    class="fixed inset-0 z-[9999] flex items-center justify-center"
    style="background-color: rgba(0, 0, 0, 0.75); backdrop-filter: blur(4px);"
>
    <div
        class="w-full max-w-md mx-4 rounded-xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="network-check-title"
    >
        {{-- Header --}}
        <div class="px-6 pt-6 pb-2 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-danger-50 dark:bg-danger-900/30">
                {{ svg('heroicon-o-wifi', 'h-8 w-8 text-danger-600 dark:text-danger-400') }}
            </div>

            <h2
                id="network-check-title"
                class="text-lg font-bold text-gray-900 dark:text-white"
            >
                Tidak Ada Koneksi Internet
            </h2>
        </div>

        {{-- Body --}}
        <div class="px-6 py-4">
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center leading-relaxed">
                Aplikasi membutuhkan koneksi internet untuk dapat digunakan.
                Silakan aktifkan jaringan internet Anda, baik melalui Wi-Fi maupun data seluler.
            </p>

            <div class="mt-5 flex items-center justify-center gap-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4">
                <x-filament::loading-indicator
                    x-show="!isRetrying"
                    class="h-5 w-5 text-gray-400 dark:text-gray-500"
                />
                <x-filament::loading-indicator
                    x-show="isRetrying"
                    class="h-5 w-5 text-primary-500"
                />
                <span
                    class="text-sm font-medium"
                    x-text="isRetrying ? 'Memeriksa koneksi...' : 'Menunggu koneksi kembali'"
                    :class="isRetrying ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400'"
                ></span>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('networkCheck', () => ({
            isConnected: true,
            hasChecked: false,
            isRetrying: false,
            retryInterval: null,

            initNetworkCheck() {
                this.checkConnection().then(() => {
                    if (!this.isConnected) {
                        this.hasChecked = true;
                    }
                });

                if (window.Capacitor?.isNativePlatform?.()) {
                    this.initCapacitorListener();
                } else {
                    this.initBrowserListeners();
                }

                this.retryInterval = setInterval(() => {
                    if (!this.isConnected) {
                        this.checkConnection();
                    }
                }, 3000);
            },

            async checkConnection() {
                if (window.Capacitor?.isNativePlatform?.()) {
                    try {
                        const Network = window.Capacitor?.Plugins?.Network;
                        if (Network?.getStatus) {
                            const status = await Network.getStatus();
                            this.isConnected = status.connected;
                            if (status.connected) {
                                this.hasChecked = true;
                            }
                            return;
                        }
                    } catch (e) {
                        // fall through
                    }
                }
                this.isConnected = navigator.onLine;
                if (navigator.onLine) {
                    this.hasChecked = true;
                }
            },

            initCapacitorListener() {
                const Network = window.Capacitor?.Plugins?.Network;
                if (!Network?.addListener) return;

                Network.addListener('networkStatusChange', (status) => {
                    this.isConnected = status.connected;
                    if (status.connected) {
                        this.hasChecked = true;
                    }
                });
            },

            initBrowserListeners() {
                this._onOnline = () => {
                    this.isConnected = true;
                    this.hasChecked = true;
                };
                this._onOffline = () => {
                    this.isConnected = false;
                    this.hasChecked = true;
                };

                window.addEventListener('online', this._onOnline);
                window.addEventListener('offline', this._onOffline);
            },

            async retryConnection() {
                this.isRetrying = true;
                await this.checkConnection();
                this.isRetrying = false;
            },

            destroy() {
                if (this.retryInterval) {
                    clearInterval(this.retryInterval);
                }
                if (this._onOnline) {
                    window.removeEventListener('online', this._onOnline);
                }
                if (this._onOffline) {
                    window.removeEventListener('offline', this._onOffline);
                }
            },
        }));
    });
</script>
