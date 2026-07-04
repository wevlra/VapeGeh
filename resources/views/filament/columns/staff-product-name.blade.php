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
            @php
                $locationId = auth()->user()?->location_id;
                $stock = $getRecord()->stocks->firstWhere('location_id', $locationId);
                $price = $stock && $stock->price > 0 ? $stock->price : $getRecord()->selling_price;
            @endphp
            <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Rp {{ number_format($price, 0, ',', '.') }}
            </span>
        </div>
        <div class="flex flex-col items-end shrink-0">
            <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Stok</span>
            @php
                $qty = $stock?->qty ?? 0;
                $color = match (true) {
                    $qty <= 0 => 'danger',
                    $qty < 10 => 'warning',
                    default => 'success',
                };
            @endphp
            <x-filament::badge :color="$color" size="sm">
                {{ $qty }}
            </x-filament::badge>
        </div>
    </div>
</div>
