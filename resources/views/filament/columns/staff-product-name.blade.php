<div class="w-full">
    {{-- Desktop View --}}
    <div class="hidden md:block font-bold">
        {{ $getRecord()->name }}
    </div>

    {{-- Mobile View --}}
    <div class="flex items-center justify-between w-full md:hidden py-1">
        <div class="flex flex-col gap-y-1">
            <span class="text-sm font-bold leading-6 text-gray-950 dark:text-white">
                {{ $getRecord()->name }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                Rp {{ number_format($getRecord()->selling_price, 0, ',', '.') }}
            </span>
        </div>
        <div class="flex flex-col items-end">
            @php
                $locationId = auth()->user()?->location_id;
                $qty = $getRecord()->stocks->firstWhere('location_id', $locationId)?->qty ?? 0;
                $color = match (true) {
                    $qty <= 0 => 'danger',
                    $qty < 10 => 'warning',
                    default => 'success',
                };
            @endphp
            <x-filament::badge :color="$color">
                {{ $qty }}
            </x-filament::badge>
        </div>
    </div>
</div>
