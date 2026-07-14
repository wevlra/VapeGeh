<div class="flex flex-col items-center justify-center space-y-4 p-4 text-center">
    <div class="rounded-full bg-primary-100 p-3 text-primary-600 dark:bg-primary-500/20 dark:text-primary-400">
        <x-heroicon-o-document-check class="h-8 w-8" />
    </div>

    <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">Laporan Berhasil Dibuat</h2>

    <p class="text-sm text-gray-500 dark:text-gray-400">
        Dokumen laporan PDF Anda sudah siap. Silakan pilih aksi di bawah ini.
    </p>

    <div class="mt-6 flex w-full flex-col gap-3 sm:flex-row sm:w-auto sm:justify-center">
        <x-filament::button
            x-data="{
                isDownloading: false,
                async download() {
                    const url = '{{ $url }}';
                    const filename = '{{ $filename }}';
                    const title = '{{ addslashes($title) }}';

                    // On Android WebView, the 'download' attribute is ignored.
                    // Fetch the file and share it as a real file via Capacitor Share plugin.
                    if (window.Capacitor?.isNativePlatform?.()) {
                        this.isDownloading = true;
                        try {
                            const { Share } = window.Capacitor.Plugins;
                            if (Share) {
                                const response = await fetch(url);
                                const blob = await response.blob();

                                // Convert blob to base64 data URI so Capacitor can share it as a file
                                const base64 = await new Promise((resolve, reject) => {
                                    const reader = new FileReader();
                                    reader.onloadend = () => resolve(reader.result);
                                    reader.onerror = reject;
                                    reader.readAsDataURL(blob);
                                });

                                await Share.share({
                                    title: title,
                                    files: [base64],
                                    dialogTitle: 'Simpan atau Bagikan ' + filename,
                                });
                                return;
                            }
                        } catch (error) {
                            console.error('Download via Capacitor failed:', error);
                            if (error.name !== 'AbortError') {
                                if (typeof FilamentNotification !== 'undefined') {
                                    new FilamentNotification()
                                        .title('Gagal mengunduh file')
                                        .danger()
                                        .send();
                                }
                            }
                        } finally {
                            this.isDownloading = false;
                        }
                        return;
                    }

                    // Web: trigger native anchor download
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }
            }"
            x-on:click.prevent="download()"
            icon="heroicon-o-arrow-down-tray"
            class="w-full sm:w-auto justify-center"
        >
            <span x-show="!isDownloading">Unduh</span>
            <span x-show="isDownloading">Mengunduh...</span>
        </x-filament::button>

        <x-filament::button
            tag="a"
            href="{{ $url }}"
            target="_blank"
            color="gray"
            icon="heroicon-o-arrow-top-right-on-square"
            class="w-full sm:w-auto justify-center"
            x-data
            x-show="!window.Capacitor?.isNativePlatform?.()"
        >
            Buka Tab Baru
        </x-filament::button>

        <x-filament::button
            x-data="{
                isSharing: false,
                async share() {
                    this.isSharing = true;
                    try {
                        const url = '{{ $url }}';
                        const filename = '{{ $filename }}';
                        const title = '{{ addslashes($title) }}';

                        // Use Capacitor Share plugin when running in Android app
                        if (window.Capacitor?.isNativePlatform?.()) {
                            const { Share } = window.Capacitor.Plugins;
                            if (Share) {
                                await Share.share({
                                    title: title,
                                    url: url,
                                    dialogTitle: title,
                                });
                                this.isSharing = false;
                                return;
                            }
                        }

                        // Web: Try to fetch and share as a File if supported
                        if (navigator.canShare) {
                            const response = await fetch(url);
                            const blob = await response.blob();
                            const file = new File([blob], filename, { type: blob.type || 'application/pdf' });

                            const shareData = {
                                title: title,
                                files: [file]
                            };

                            if (navigator.canShare(shareData)) {
                                await navigator.share(shareData);
                                this.isSharing = false;
                                return;
                            }
                        }

                        // Fallback 1: Share URL if file share not supported but navigator.share exists
                        if (navigator.share) {
                            await navigator.share({
                                title: title,
                                url: url
                            });
                            this.isSharing = false;
                            return;
                        }

                        // Fallback 2: Copy to clipboard
                        if (window.isSecureContext && navigator.clipboard) {
                            navigator.clipboard.writeText(url);
                        } else {
                            const textArea = document.createElement('textarea');
                            textArea.value = url;
                            textArea.style.position = 'absolute';
                            textArea.style.left = '-999999px';
                            document.body.prepend(textArea);
                            textArea.select();
                            try {
                                document.execCommand('copy');
                            } catch (error) {
                                console.error(error);
                            } finally {
                                textArea.remove();
                            }
                        }

                        // Show success notification
                        if (typeof FilamentNotification !== 'undefined') {
                            new FilamentNotification()
                                .title('Perangkat tidak mendukung Share File. Tautan disalin sebagai gantinya!')
                                .warning()
                                .send();
                        } else {
                            alert('Tautan disalin ke clipboard!');
                        }
                    } catch (error) {
                        console.error('Sharing failed:', error);
                        if (error.name !== 'AbortError') {
                            if (typeof FilamentNotification !== 'undefined') {
                                new FilamentNotification()
                                    .title('Gagal membagikan file')
                                    .danger()
                                    .send();
                            }
                        }
                    } finally {
                        this.isSharing = false;
                    }
                }
            }"
            x-on:click.prevent="share()"
            color="info"
            icon="heroicon-o-share"
            class="w-full sm:w-auto justify-center"
        >
            <span x-show="!isSharing">Bagikan</span>
            <span x-show="isSharing">Memproses...</span>
        </x-filament::button>
    </div>
</div>
