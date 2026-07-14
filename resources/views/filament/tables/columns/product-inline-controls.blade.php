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

<div class="flex flex-col gap-2 min-w-[200px] p-2">
    @if(isset($showPrice) && $showPrice)
    <div class="flex items-center space-x-2">
        <label class="text-sm font-medium text-gray-500 whitespace-nowrap w-24">{{ $priceLabel ?? 'Harga:' }}</label>
        <div class="w-full">
            <x-filament::input.wrapper prefix="Rp">
                <x-filament::input 
                    type="text" 
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
                />
            </x-filament::input.wrapper>
        </div>
    </div>
    @endif
    
    <div class="flex items-center space-x-2">
        <label class="text-sm font-medium text-gray-500 whitespace-nowrap w-24">{{ $qtyLabel ?? 'Jumlah:' }}</label>
        
        <x-filament::icon-button 
            icon="heroicon-m-minus" 
            color="gray"
            size="sm"
            wire:click="decrementItem({{ $record->id }})" 
        />
        
        <div class="w-24">
            <x-filament::input.wrapper>
                <x-filament::input 
                    type="number" 
                    value="{{ $qty }}"
                    wire:change="updateItemQty({{ $record->id }}, $event.target.value)"
                    class="text-center font-bold"
                />
            </x-filament::input.wrapper>
        </div>
        
        <x-filament::icon-button 
            icon="heroicon-m-plus" 
            color="gray"
            size="sm"
            wire:click="incrementItem({{ $record->id }})" 
        />
    </div>

    @if(isset($showDifference) && $showDifference)
    <div class="flex items-center space-x-2 mt-2 border-t pt-2 border-gray-200 dark:border-gray-700">
        <label class="text-sm font-medium text-gray-500 whitespace-nowrap w-24">Stok Sistem:</label>
        <span class="font-bold text-sm text-gray-700 dark:text-gray-300">
            {{ $systemQty }}
        </span>
    </div>
    <div class="flex items-center space-x-2">
        <label class="text-sm font-medium text-gray-500 whitespace-nowrap w-24">Selisih:</label>
        <span class="font-bold text-sm {{ $diff > 0 ? 'text-success-600 dark:text-success-400' : ($diff < 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-500') }}">
            {{ $diff > 0 ? '+'.$diff : $diff }}
        </span>
    </div>
    @endif
</div>
