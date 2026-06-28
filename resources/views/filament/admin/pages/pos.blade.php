<x-filament-panels::page>
    @vite(['resources/css/app.css'])

    @php
        $cartItems = $this->getCartItems();
        $cartTotal = $this->getCartTotal();
    @endphp

    <div class="flex flex-col lg:flex-row gap-6">
        {{-- Product List --}}
        <div class="flex-1 min-w-0">
            {{ $this->content }}
        </div>

        {{-- Cart Summary --}}
        <div class="lg:w-[400px] shrink-0">
            <div class="sticky top-4">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <x-filament::icon
                                    name="heroicon-o-shopping-cart"
                                    class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                />
                                <span>Cart</span>
                                @if (count($cartItems) > 0)
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-100 dark:bg-primary-900/30 text-xs font-medium text-primary-700 dark:text-primary-400">
                                        {{ collect($cartItems)->sum('qty') }}
                                    </span>
                                @endif
                            </div>
                            @if (count($cartItems) > 0)
                                <button
                                    wire:click="clearCart"
                                    type="button"
                                    class="text-xs text-gray-400 hover:text-danger-500 transition-colors"
                                >
                                    Clear All
                                </button>
                            @endif
                        </div>
                    </x-slot>

                    <div class="space-y-0 divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse ($cartItems as $index => $item)
                            <div class="flex items-start gap-3 py-3 first:pt-0">
                                {{-- Item Info --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $item['name'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        Rp {{ number_format($item['price'], 0, ',', '.') }}
                                    </p>
                                </div>

                                {{-- Qty Controls --}}
                                <div class="flex items-center gap-1">
                                    <x-filament::icon-button
                                        icon="heroicon-m-minus"
                                        size="xs"
                                        color="gray"
                                        class="border border-gray-200 dark:border-gray-600"
                                        label="Decrease quantity"
                                        wire:click="updateQty({{ $index }}, {{ $item['qty'] - 1 }})"
                                    />
                                    <span class="w-8 text-center text-sm font-semibold text-gray-900 dark:text-white tabular-nums">
                                        {{ $item['qty'] }}
                                    </span>
                                    <x-filament::icon-button
                                        icon="heroicon-m-plus"
                                        size="xs"
                                        color="gray"
                                        class="border border-gray-200 dark:border-gray-600"
                                        label="Increase quantity"
                                        wire:click="updateQty({{ $index }}, {{ $item['qty'] + 1 }})"
                                    />
                                </div>

                                {{-- Subtotal & Remove --}}
                                <div class="text-right shrink-0 min-w-[88px]">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white tabular-nums">
                                        Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                    </p>
                                    <button
                                        wire:click="removeCartItem({{ $index }})"
                                        type="button"
                                        class="text-xs text-gray-400 hover:text-danger-500 transition-colors mt-0.5"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-8 text-gray-400 dark:text-gray-500">
                                <x-filament::icon
                                    name="heroicon-o-shopping-cart"
                                    class="w-12 h-12 mb-3 text-gray-300 dark:text-gray-600"
                                />
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Cart is empty
                                </p>
                                <p class="text-xs mt-1">
                                    Click a product to add
                                </p>
                            </div>
                        @endforelse

                        {{-- Total & Checkout --}}
                        @if (count($cartItems) > 0)
                            <div class="pt-4">
                                <div class="flex justify-between items-center mb-4">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">Total</span>
                                    <span class="text-xl font-bold text-primary-600 dark:text-primary-400 tabular-nums">
                                        Rp {{ number_format($cartTotal, 0, ',', '.') }}
                                    </span>
                                </div>

                                <x-filament::button
                                    wire:click="mountAction('checkout')"
                                    class="w-full"
                                    size="lg"
                                    icon="heroicon-m-credit-card"
                                >
                                    Checkout
                                </x-filament::button>
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
