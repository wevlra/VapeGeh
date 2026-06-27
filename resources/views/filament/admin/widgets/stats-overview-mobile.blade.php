<x-filament-widgets::widget class="fi-wi-stats-mobile md:hidden">
    <x-filament::section>
        <x-slot name="heading">Today's Summary</x-slot>

        <dl class="grid grid-cols-2 gap-3">
            <div class="rounded-md bg-gray-50 p-2.5 dark:bg-gray-900/40">
                <dt class="text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Stock In</dt>
                <dd class="mt-0.5 text-base font-semibold text-gray-950 dark:text-white">
                    {{ number_format($stockIn) }}
                </dd>
            </div>
            <div class="rounded-md bg-gray-50 p-2.5 dark:bg-gray-900/40">
                <dt class="text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Stock Out</dt>
                <dd class="mt-0.5 text-base font-semibold text-gray-950 dark:text-white">
                    {{ number_format($stockOut) }}
                </dd>
            </div>
            <div class="rounded-md bg-gray-50 p-2.5 dark:bg-gray-900/40">
                <dt class="text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Revenue</dt>
                <dd class="mt-0.5 text-base font-semibold text-success-600 dark:text-success-400">
                    Rp {{ number_format($revenue, 0, ',', '.') }}
                </dd>
            </div>
            <div class="rounded-md bg-gray-50 p-2.5 dark:bg-gray-900/40">
                <dt class="text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Profit</dt>
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
                View Report
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
