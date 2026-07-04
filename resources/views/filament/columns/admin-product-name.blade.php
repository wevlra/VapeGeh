<div class="w-full">
    {{-- Desktop View --}}
    <div class="hidden md:block">
        {{ $getRecord()->name }}
    </div>

    {{-- Mobile View --}}
    <div class="flex items-center justify-between w-full md:hidden py-1.5 gap-x-4">
        <div class="flex flex-col min-w-0">
            <span class="text-sm font-semibold text-gray-950 dark:text-white truncate">
                {{ $getRecord()->name }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Rp {{ number_format($getRecord()->purchase_price, 0, ',', '.') }}
            </span>
        </div>
        <div class="flex flex-col items-end shrink-0">
            <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Jual</span>
            <span class="text-sm font-semibold text-primary-600 dark:text-primary-400">
                Rp {{ number_format($getRecord()->selling_price, 0, ',', '.') }}
            </span>
        </div>
    </div>
</div>
