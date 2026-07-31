<x-filament-widgets::widget class="fi-wi-stock-asset-mobile md:hidden">
    <x-filament::section>
        <x-slot name="heading">Informasi Lainnya</x-slot>

        <dl class="grid grid-cols-2 gap-3">
            <div class="rounded-md bg-gray-50 p-2.5 dark:bg-gray-900/40">
                <dt class="text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Stok</dt>
                <dd class="mt-0.5 text-base font-semibold text-warning-600 dark:text-warning-400">
                    {{ number_format($totalStock, 0, ',', '.') }}
                </dd>
            </div>
            <div class="rounded-md bg-gray-50 p-2.5 dark:bg-gray-900/40">
                <dt class="text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Aset</dt>
                <dd class="mt-0.5 text-base font-semibold text-primary-600 dark:text-primary-400">
                    Rp {{ number_format($totalAsset, 0, ',', '.') }}
                </dd>
            </div>
            <div class="rounded-md bg-gray-50 p-2.5 dark:bg-gray-900/40">
                <dt class="text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Produk</dt>
                <dd class="mt-0.5 text-base font-semibold text-success-600 dark:text-success-400">
                    {{ number_format($totalProducts, 0, ',', '.') }}
                </dd>
            </div>
            <div class="rounded-md bg-gray-50 p-2.5 dark:bg-gray-900/40">
                <dt class="text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Staff</dt>
                <dd class="mt-0.5 text-base font-semibold text-info-600 dark:text-info-400">
                    {{ number_format($totalStaff, 0, ',', '.') }}
                </dd>
            </div>
        </dl>
    </x-filament::section>
</x-filament-widgets::widget>
