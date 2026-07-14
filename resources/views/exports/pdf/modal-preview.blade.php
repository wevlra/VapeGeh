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
            tag="a"
            href="{{ $url }}"
            download="{{ $filename }}"
            icon="heroicon-o-arrow-down-tray"
            class="w-full sm:w-auto justify-center"
        >
            Unduh
        </x-filament::button>

        <x-filament::button
            tag="a"
            href="{{ $url }}"
            target="_blank"
            color="gray"
            icon="heroicon-o-arrow-top-right-on-square"
            class="w-full sm:w-auto justify-center"
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
                        
                        // Try to fetch and share as a File if supported
                        if (navigator.canShare) {
                            const response = await fetch(url);
                            const blob = await response.blob();
                            const file = new File([blob], filename, { type: blob.type || 'application/pdf' });
                            
                            const shareData = {
                                title: '{{ addslashes($title) }}',
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
                                title: '{{ addslashes($title) }}',
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
