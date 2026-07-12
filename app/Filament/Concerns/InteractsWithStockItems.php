<?php

namespace App\Filament\Concerns;

use App\Models\Product;

trait InteractsWithStockItems
{
    public function incrementItem(int $productId): void
    {
        $this->updateItemQty($productId, ($this->getItemQty($productId) + 1));
    }

    public function decrementItem(int $productId): void
    {
        $current = $this->getItemQty($productId);
        if ($current > 0) {
            $this->updateItemQty($productId, $current - 1);
        }
    }

    protected function getItemQty(int $productId): int
    {
        $qtyField = $this->getQtyField();

        return collect($this->items)->firstWhere('product_id', $productId)[$qtyField] ?? 0;
    }

    protected function getQtyField(): string
    {
        return 'qty';
    }

    public function updateItemQty(int $productId, $qty): void
    {
        $qty = (int) $qty;
        if ($qty <= 0) {
            $this->removeItem($productId);

            return;
        }

        $record = Product::find($productId);
        if (! $record) {
            return;
        }

        $existingIndex = collect($this->items)->search(fn ($item) => $item['product_id'] === $productId);

        $itemData = $this->buildItemData($record, $qty, $existingIndex !== false ? $this->items[$existingIndex] : null);

        if ($existingIndex !== false) {
            $this->items[$existingIndex] = $itemData;
        } else {
            $this->items[] = $itemData;
        }
    }

    public function updateItemPrice(int $productId, $price): void
    {
        $existingIndex = collect($this->items)->search(fn ($item) => $item['product_id'] === $productId);
        if ($existingIndex !== false) {
            $this->items[$existingIndex]['price'] = (float) $price;
            // Also manual_price for StockOut if needed
            if (array_key_exists('manual_price', $this->items[$existingIndex])) {
                $this->items[$existingIndex]['manual_price'] = (float) $price;
            }
        } else {
            $this->updateItemQty($productId, 1);
            $existingIndex = collect($this->items)->search(fn ($item) => $item['product_id'] === $productId);
            if ($existingIndex !== false) {
                $this->items[$existingIndex]['price'] = (float) $price;
                if (array_key_exists('manual_price', $this->items[$existingIndex])) {
                    $this->items[$existingIndex]['manual_price'] = (float) $price;
                }
            }
        }
    }
}
