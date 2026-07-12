<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\StockMovement;

class ReceiptController extends Controller
{
    public function __invoke(StockMovement $stockMovement)
    {
        $this->authorizeAccess($stockMovement);

        $stockMovement->load(['product', 'location', 'creator', 'buyer', 'related', 'related.items.product']);

        if (request()->query('format') === 'text') {
            return view('receipts.receipt_text', ['movement' => $stockMovement]);
        }

        if (request()->query('format') === 'json') {
            return response()->json($this->formatReceiptData($stockMovement));
        }

        return view('receipts.receipt', ['movement' => $stockMovement]);
    }

    public function sale(Sale $sale)
    {
        $movement = $sale->stockMovements()->firstOrFail();

        $this->authorizeAccess($movement);

        $movement->load(['product', 'location', 'creator', 'buyer', 'related']);

        if (request()->query('format') === 'text') {
            return view('receipts.receipt_text', ['movement' => $movement]);
        }

        if (request()->query('format') === 'json') {
            return response()->json($this->formatReceiptData($movement));
        }

        return view('receipts.receipt', ['movement' => $movement]);
    }

    private function authorizeAccess(StockMovement $stockMovement): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'staff']), 403);

        if (auth()->user()->role === 'staff' && $stockMovement->location_id !== auth()->user()->location_id) {
            abort(403);
        }
    }

    private function formatReceiptData(StockMovement $movement)
    {
        $products = [];
        // Support for sales with related stock movements (multiple items)
        $items = $movement->related?->items ?? collect([$movement]);

        foreach ($items as $item) {
            if (! $item->product) {
                continue;
            }

            $products[] = [
                'id' => (string) $item->product_id,
                'name' => $item->product->name,
                'qty' => abs($item->qty),
                'unit' => 'pcs',
                'price' => $item->price ?? 0,
            ];
        }

        return [
            'header' => [
                'storeName' => 'VapeGeh',
                'address' => 'Jl. Merdeka No. 123', // Hardcoded as per receipt blade
                'phone' => '081234567890',
                'receiptNo' => $movement->reference ?? 'TX-'.$movement->id,
                'date' => $movement->created_at->format('Y-m-d H:i'),
                'cashier' => $movement->creator->name ?? 'Kasir',
            ],
            'products' => $products,
            'taxPercent' => 0,
            'globalDiscount' => 0,
            'paymentMethod' => 'CASH',
            'amountPaid' => $items->sum(function ($item) {
                return abs($item->qty) * ($item->price ?? 0);
            }),
        ];
    }
}
