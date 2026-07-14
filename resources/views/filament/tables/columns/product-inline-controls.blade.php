@php
    $record = $getRecord();
    $state = $getState() ?? [];
    $existing = collect($this->items)->firstWhere('product_id', $record->id);
    
    $showPrice = $showPrice ?? $state['showPrice'] ?? false;
    $priceLabel = $priceLabel ?? $state['priceLabel'] ?? 'Harga:';
    $qtyLabel = $qtyLabel ?? $state['qtyLabel'] ?? 'Jumlah:';
    $defaultPriceCallable = $defaultPriceCallable ?? $state['defaultPriceCallable'] ?? null;
    $defaultQty = $defaultQty ?? $state['defaultQty'] ?? null;

    $qtyField = $qtyField ?? 'qty';
    $qty = $existing[$qtyField] ?? ($defaultQty ? $defaultQty($record) : 0);
    
    $priceField = $priceField ?? 'price';
    $price = $existing[$priceField] ?? ($defaultPriceCallable ? $defaultPriceCallable($record) : 0);
    
    $showDifference = $showDifference ?? $state['showDifference'] ?? false;
    $systemQty = $defaultQty ? $defaultQty($record) : 0;
    $diff = $qty - $systemQty;
@endphp

<div class="flex flex-col gap-3 w-full max-w-sm sm:min-w-[240px] p-2 sm:p-0">
    @if(isset($showPrice) && $showPrice)
    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 w-full">
        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden sm:block w-20 shrink-0">{{ $priceLabel ?? 'Harga' }}</label>
        <div class="w-full">
            <x-filament::input.wrapper prefix="Rp" class="shadow-sm">
                <x-filament::input 
                    type="text" 
                    placeholder="{{ $priceLabel ?? 'Harga' }}"
                    x-data="{
                        price: '{{ $price ? number_format($price, 0, '', '') : '' }}',
                        format() {
                            let val = this.$el.value.replace(/[^0-9]/g, '');
                            if (!val) {
                                this.$el.value = '';
                                return;
                            }
                            this.$el.value = parseInt(val, 10).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        }
                    }"
                    x-init="$el.value = price ? parseInt(price, 10).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''"
                    @input="format()"
                    @change="$wire.updateItemPrice({{ $record->id }}, $el.value.replace(/\./g, ''))"
                    class="font-medium text-gray-900 dark:text-white"
                />
            </x-filament::input.wrapper>
        </div>
    </div>
    @endif
    
    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 w-full">
        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden sm:block w-20 shrink-0">{{ $qtyLabel ?? 'Jumlah' }}</label>
        
        <div class="flex items-center justify-between sm:justify-start w-full gap-3">
            <x-filament::icon-button 
                icon="heroicon-m-minus" 
                color="danger"
                size="md"
                class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 shrink-0 rounded-lg shadow-sm w-10 h-10 flex items-center justify-center"
                wire:click="decrementItem({{ $record->id }})" 
            />
            
            <div class="flex-1 sm:flex-none sm:w-24 min-w-[80px]">
                <x-filament::input.wrapper class="shadow-sm">
                    <x-filament::input 
                        type="number" 
                        value="{{ $qty }}"
                        wire:change="updateItemQty({{ $record->id }}, $event.target.value)"
                        class="text-center font-bold text-lg p-2 w-full"
                        placeholder="0"
                    />
                </x-filament::input.wrapper>
            </div>
            
            <x-filament::icon-button 
                icon="heroicon-m-plus" 
                color="success"
                size="md"
                class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 shrink-0 rounded-lg shadow-sm w-10 h-10 flex items-center justify-center"
                wire:click="incrementItem({{ $record->id }})" 
            />
        </div>
    </div>

    @if(isset($showDifference) && $showDifference)
    <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
        <div class="text-xs text-gray-500">
            Stok Sistem: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $systemQty }}</span>
        </div>
        <div class="text-xs text-gray-500">
            Selisih: 
            <span class="font-bold {{ $diff > 0 ? 'text-success-600 dark:text-success-400' : ($diff < 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-500') }}">
                {{ $diff > 0 ? '+'.$diff : $diff }}
            </span>
        </div>
    </div>
    @endif
</div>
