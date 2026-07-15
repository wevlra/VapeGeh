<div>
    @if (count($this->items))
        <div class="flex flex-wrap gap-2">
            @foreach ($this->items as $item)
                @php
                    $displayPrice = $item['price'] ?? $item['manual_price'] ?? 0;
                    $displayQty = $item['qty'] ?? $item['quantity'] ?? 0;
                @endphp
                <x-filament::badge color="info" class="gap-1.5 py-1 pl-3 pr-1.5">
                    <span>
                        {{ $item['name'] ?? $item['product_id'] }}
                        <span class="text-xs opacity-75">
                            × {{ $displayQty }}
                            · Rp {{ number_format($displayPrice, 0, ',', '.') }}
                        </span>
                    </span>
                    <button
                        type="button"
                        wire:click="removeItem('{{ $item['product_id'] }}')"
                        class="ml-1 rounded-full p-0.5 hover:bg-white/20 transition-colors"
                    >
                        <x-filament::icon
                            name="heroicon-m-x-mark"
                            class="h-3.5 w-3.5"
                        />
                    </button>
                </x-filament::badge>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400 italic">
            Belum ada item yang dipilih.
        </p>
    @endif
</div>
