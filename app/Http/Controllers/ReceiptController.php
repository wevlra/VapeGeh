<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\StockEntry;
use App\Models\StockMovement;
use App\Models\StockTransfer;

class ReceiptController extends Controller
{
    public function __invoke(StockMovement $stockMovement)
    {
        $this->authorizeAccess($stockMovement);

        $stockMovement->load(['product', 'location', 'creator', 'buyer', 'related']);

        if ($stockMovement->related instanceof Sale) {
            $stockMovement->related->load(['buyer', 'items.product']);
        } elseif ($stockMovement->related instanceof StockEntry || $stockMovement->related instanceof StockTransfer) {
            $stockMovement->related->load(['items.product']);
        }

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
        $sale->load(['buyer', 'items.product']);

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
        $related = $movement->related;
        $isSale = $related instanceof Sale;
        $isStockEntry = $related instanceof StockEntry;
        $isStockTransfer = $related instanceof StockTransfer;

        // Compute reference number
        $refNumber = match (true) {
            $isSale => $related->invoice_number,
            $isStockTransfer => $related->transfer_number,
            default => '#SM-'.$movement->id,
        };

        // Get all line items
        $items = $related?->items ?? collect([$movement]);
        $products = [];

        foreach ($items as $item) {
            if (! $item->product) {
                continue;
            }

            $price = (float) match (true) {
                isset($item->unit_price) && $item->unit_price > 0 => $item->unit_price,
                ! empty($item->price) => $item->price,
                default => $item->product?->price_1 ?? 0,
            };

            $products[] = [
                'id' => (string) $item->product_id,
                'name' => $item->product->name,
                'qty' => abs((int) ($item->qty ?? $item->quantity ?? 0)),
                'unit' => 'pcs',
                'price' => $price,
            ];
        }

        $extraCosts = $related?->additional_costs ?? $movement->additional_costs ?? [];
        if (! empty($extraCosts) && is_array($extraCosts)) {
            foreach ($extraCosts as $index => $cost) {
                $products[] = [
                    'id' => 'extra-'.$index,
                    'name' => $cost['description'] ?? $cost['name'] ?? 'Biaya Tambahan',
                    'qty' => 1,
                    'unit' => 'item',
                    'price' => (float) ($cost['amount'] ?? 0),
                ];
            }
        }

        // Payment method
        $paymentMethod = match (true) {
            $isSale => $related->payment_method ?? 'cash',
            $isStockEntry && $related->payment_method => $related->payment_method,
            default => 'cash',
        };

        // Total
        $total = match (true) {
            $isSale => (float) $related->total,
            $isStockEntry => (float) $related->total_amount,
            $isStockTransfer => (float) $related->items->sum(fn ($i) => $i->qty * (float) ($i->price ?? 0)),
            default => (float) collect($products)->sum(fn ($p) => $p['price'] * $p['qty']),
        };

        $paidAmount = match (true) {
            $isSale => (float) $related->paid_amount,
            $isStockEntry => $total,
            default => $total,
        };

        // Logo as base64
        $logoBase64 = null;
        $logoPath = public_path('assets/images/logo-stacked-dark-tr.png');
        if (file_exists($logoPath)) {
            $logoBase64 = base64_encode(file_get_contents($logoPath));
        }

        return [
            'header' => [
                'storeName' => config('store.name'),
                'address' => config('store.address'),
                'phone' => config('store.phone'),
                'receiptNo' => $refNumber,
                'date' => $movement->created_at->format('Y-m-d H:i'),
                'cashier' => $movement->creator?->name ?? 'Kasir',
                'note' => config('store.receipt_footer'),
            ],
            'logoBase64' => $logoBase64,
            'products' => $products,
            'taxPercent' => 0,
            'globalDiscount' => 0,
            'paymentMethod' => $paymentMethod,
            'total' => $total,
            'amountPaid' => $paidAmount,
            'change' => max(0, $paidAmount - $total),
        ];
    }
}
