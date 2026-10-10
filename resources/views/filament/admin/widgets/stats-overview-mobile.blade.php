<x-filament-widgets::widget class="fi-wi-stats-mobile md:hidden">
    <x-filament::section>
        <x-slot name="heading">Ringkasan {{ $periodHeading }}</x-slot>

        <dl class="grid grid-cols-2 gap-3">
            <div class="rounded-md bg-gray-50 p-2.5 dark:bg-gray-900/40">
                <dt class="text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Stok Masuk</dt>
                <dd class="mt-0.5 text-base font-semibold text-gray-950 dark:text-white">
                    {{ number_format($stockIn, 0, ',', '.') }}
                </dd>
            </div>
            <div class="rounded-md bg-gray-50 p-2.5 dark:bg-gray-900/40">
                <dt class="text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Stok Keluar</dt>
                <dd class="mt-0.5 text-base font-semibold text-gray-950 dark:text-white">
                    {{ number_format($stockOut, 0, ',', '.') }}
                </dd>
            </div>
                        <div class="rounded-md bg-gray-50 p-2.5 dark:bg-gray-900/40">
                <dt class="text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Pembelian</dt>
                <dd class="mt-0.5 text-base font-semibold text-warning-600 dark:text-warning-400">
                    Rp {{ number_format($totalPurchase, 0, ',', '.') }}
                </dd>
            </div>
            <div class="rounded-md bg-gray-50 p-2.5 dark:bg-gray-900/40">
                <dt class="text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Pengeluaran</dt>
                <dd class="mt-0.5 text-base font-semibold text-danger-600 dark:text-danger-400">
                    Rp {{ number_format($opExpense, 0, ',', '.') }}
                </dd>
            </div>
            <div class="rounded-md bg-gray-50 p-2.5 dark:bg-gray-900/40">
                <dt class="text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Pendapatan</dt>
                <dd class="mt-0.5 text-base font-semibold text-success-600 dark:text-success-400">
                    Rp {{ number_format($revenue, 0, ',', '.') }}
                </dd>
            </div>
            <div class="rounded-md bg-gray-50 p-2.5 dark:bg-gray-900/40">
                <dt class="text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Untung</dt>
                <dd @class([
                    'mt-0.5 text-base font-semibold',
                    'text-success-600 dark:text-success-400' => $profit >= 0,
                    'text-danger-600 dark:text-danger-400' => $profit < 0,
                ])>
                    Rp {{ number_format($profit, 0, ',', '.') }}
                </dd>
            </div>
        </dl>

        <div class="mt-3">
            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Admin\Pages\SalesReport::getUrl() }}"
                color="primary"
                size="sm"
                class="w-full justify-center"
                icon="heroicon-o-chart-bar"
            >
                Lihat Laporan
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
