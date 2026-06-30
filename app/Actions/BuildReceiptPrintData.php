<?php

namespace App\Actions;

use App\Models\Sale;
use App\Models\StockEntry;
use App\Models\StockMovement;
use App\Models\StockTransfer;

class BuildReceiptPrintData
{
    /**
     * @return array<string, mixed>
     */
    public function build(StockMovement $movement): array
    {
        $movement->loadMissing(['product', 'location', 'creator', 'buyer', 'related', 'related.items.product']);

        $location = $movement->location;
        $related = $movement->related;

        $refNumber = match (true) {
            $related instanceof Sale => $related->invoice_number,
            $related instanceof StockTransfer => $related->transfer_number,
            default => '#SM-'.$movement->id,
        };

        $typeLabel = match ($movement->type) {
            'in' => 'STOK MASUK',
            'out' => 'STOK KELUAR',
            'transfer_in' => 'TRANSFER MASUK',
            'transfer_out' => 'TRANSFER KELUAR',
            'adjustment' => 'PENYESUAIAN',
            default => strtoupper($movement->type),
        };

        $items = $this->buildItems($movement);
        $total = $this->calculateTotal($movement, $items);

        return [
            'reference' => $refNumber,
            'type' => $typeLabel,
            'date' => $movement->created_at->format('d M Y H:i'),
            'store' => [
                'name' => config('store.name'),
                'address' => $location->address ?? config('store.address'),
                'phone' => config('store.phone'),
            ],
            'logo_url' => asset('assets/images/logo-thermal.png'),
            'cashier' => $movement->creator?->name ?? '-',
            'location' => $location->name,
            'buyer' => $movement->buyer?->name ?? null,
            'items' => $items,
            'total' => $total,
            'payment_method' => $this->paymentMethod($movement, $related),
            'notes' => $movement->notes,
            'additional_costs' => $movement->additional_costs,
        ];
    }

    /**
     * @return array<int, array{name: string, qty: int, price: float, subtotal: float}>
     */
    private function buildItems(StockMovement $movement): array
    {
        $related = $movement->related;

        if ($related instanceof Sale) {
            return $related->items->map(fn ($item) => [
                'name' => $item->product?->name ?? 'Produk #'.$item->product_id,
                'qty' => (int) $item->qty,
                'price' => (float) $item->price,
                'subtotal' => (float) $item->subtotal,
            ])->values()->toArray();
        }

        if ($related instanceof StockTransfer) {
            return $related->items->map(fn ($item) => [
                'name' => $item->product?->name ?? 'Produk #'.$item->product_id,
                'qty' => (int) $item->qty,
                'price' => 0,
                'subtotal' => 0,
            ])->values()->toArray();
        }

        if ($related instanceof StockEntry) {
            return $related->items->map(fn ($item) => [
                'name' => $item->product?->name ?? 'Produk #'.$item->product_id,
                'qty' => (int) $item->qty,
                'price' => (float) ($item->unit_price ?? 0),
                'subtotal' => (int) $item->qty * (float) ($item->unit_price ?? 0),
            ])->values()->toArray();
        }

        // fallback: single product movement
        return [
            [
                'name' => $movement->product?->name ?? 'Produk #'.$movement->product_id,
                'qty' => abs((int) $movement->quantity),
                'price' => (float) ($movement->unit_price ?? 0),
                'subtotal' => abs((int) $movement->quantity) * (float) ($movement->unit_price ?? 0),
            ],
        ];
    }

    private function calculateTotal(StockMovement $movement, array $items): float
    {
        $related = $movement->related;

        if ($related instanceof Sale) {
            return (float) $related->total;
        }

        return round(array_sum(array_column($items, 'subtotal')), 2);
    }

    private function paymentMethod(StockMovement $movement, mixed $related): ?string
    {
        if ($related instanceof Sale) {
            return match ($related->payment_method) {
                'cash' => 'Cash',
                'transfer' => 'Bank Transfer',
                'qris' => 'QRIS',
                default => ucfirst($related->payment_method ?? '-'),
            };
        }

        if ($related instanceof StockEntry) {
            return match ($related->payment_method) {
                'cash' => 'Cash',
                'transfer' => 'Bank Transfer',
                'qris' => 'QRIS',
                default => ucfirst($related->payment_method ?? '-'),
            };
        }

        return null;
    }
}
