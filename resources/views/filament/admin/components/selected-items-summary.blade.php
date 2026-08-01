<x-filament::section>
    <x-slot name="heading">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    name="heroicon-o-clipboard-document-check"
                    class="w-5 h-5 text-gray-500 dark:text-gray-400"
                />
                <span>Ringkasan Pilihan</span>
                @if (count($getLivewire()->items) > 0)
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-100 dark:bg-primary-900/30 text-xs font-medium text-primary-700 dark:text-primary-400">
                        {{ collect($getLivewire()->items)->sum('qty') }}
                    </span>
                @endif
            </div>
            @if (count($getLivewire()->items) > 0)
                <button
                    wire:click="$set('items', [])"
                    type="button"
                    class="text-xs text-gray-400 hover:text-danger-500 transition-colors"
                >
                    Kosongkan
                </button>
            @endif
        </div>
    </x-slot>

    @php
        $totalAll = 0;
    @endphp
    <div class="space-y-0 divide-y divide-gray-100 dark:divide-gray-700/50">
        @forelse ($getLivewire()->items as $index => $item)
            @php
                $price = $item['price'] ?? $item['purchase_price'] ?? 0;
                if (array_key_exists('price_1_type', $item)) {
                    $type = $item['price_1_type'] ?? null;
                    if ($type === 'default' || !isset($item['price'])) {
                        $product = \App\Models\Product::find($item['product_id']);
                        $price = $product ? (float) $product->price_1 : 0;
                    }
                }
                $subtotal = $price * $item['qty'];
                $totalAll += $subtotal;
            @endphp
            <div class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ $item['name'] ?? '' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Rp {{ number_format($price, 0, ',', '.') }}
                    </p>
                </div>

                <div class="flex items-center gap-1">
                    <x-filament::icon-button
                        icon="heroicon-m-minus"
                        size="xs"
                        color="gray"
                        class="border border-gray-200 dark:border-gray-600"
                        label="Kurangi jumlah"
                        wire:click="decrementItem('{{ $item['product_id'] }}')"
                    />
                    <span class="w-8 text-center text-sm font-semibold text-gray-900 dark:text-white tabular-nums">
                        {{ $item['qty'] }}
                    </span>
                    <x-filament::icon-button
                        icon="heroicon-m-plus"
                        size="xs"
                        color="gray"
                        class="border border-gray-200 dark:border-gray-600"
                        label="Tambah jumlah"
                        wire:click="incrementItem('{{ $item['product_id'] }}')"
                    />
                </div>

                <div class="text-right shrink-0 min-w-[88px]">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white tabular-nums">
                        Rp {{ number_format($subtotal, 0, ',', '.') }}
                    </p>
                    <button
                        wire:click="removeItem('{{ $item['product_id'] }}')"
                        type="button"
                        class="text-xs text-gray-400 hover:text-danger-500 transition-colors mt-0.5"
                    >
                        Hapus
                    </button>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-8 text-gray-400 dark:text-gray-500">
                <x-filament::icon
                    name="heroicon-o-clipboard-document-list"
                    class="w-12 h-12 mb-3 text-gray-300 dark:text-gray-600"
                />
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Belum ada produk dipilih
                </p>
                <p class="text-xs mt-1">
                    Pilih produk terlebih dahulu
                </p>
            </div>
        @endforelse

        {{-- Total Price --}}
        @if (count($getLivewire()->items) > 0)
            <div class="pt-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">Total</span>
                    <span class="text-lg font-bold text-primary-600 dark:text-primary-400 tabular-nums">
                        Rp {{ number_format($totalAll, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        @endif
    </div>
</x-filament::section>
