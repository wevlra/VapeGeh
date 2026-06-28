<div class="space-y-1">
    @php
        $cartItems = $this->getCartItems();
        $cartTotal = $this->getCartTotal();
    @endphp
    @foreach ($cartItems as $item)
        <div class="flex justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-300">
                {{ $item['name'] }} &times; {{ $item['qty'] }}
            </span>
            <span class="font-medium text-gray-900 dark:text-white">
                Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
            </span>
        </div>
    @endforeach
    <div class="flex justify-between text-base font-bold pt-2 mt-2">
        <span class="text-gray-900 dark:text-white">Total</span>
        <span class="text-primary-600 dark:text-primary-400">
            Rp {{ number_format($cartTotal, 0, ',', '.') }}
        </span>
    </div>
</div>
