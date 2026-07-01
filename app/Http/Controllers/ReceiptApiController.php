<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Traits\AuthorizesReceiptAccess;
use Illuminate\Http\JsonResponse;

class ReceiptApiController extends Controller
{
    use AuthorizesReceiptAccess;

    public function show(StockMovement $stockMovement): JsonResponse
    {
        $this->authorizeAccess($stockMovement);

        $stockMovement->load('location', 'creator', 'buyer', 'related');

        return response()->json($this->buildPayload($stockMovement));
    }

    public function sale(Sale $sale): JsonResponse
    {
        $movement = $sale->stockMovements()->first();

        abort_unless($movement, 404);

        $this->authorizeAccess($movement);

        $movement->load('location', 'creator', 'buyer', 'related');

        return response()->json($this->buildPayload($movement));
    }

    private function buildPayload(StockMovement $movement): array
    {
        $related = $movement->related;

        return [
            'ref_number' => match (true) {
                $related instanceof Sale => $related->invoice_number,
                $related instanceof StockTransfer => $related->transfer_number,
                default => '#SM-'.$movement->id,
            },
            'type_label' => match ($movement->type) {
                'in' => 'STOK MASUK',
                'out' => 'STOK KELUAR',
                'transfer_in' => 'TRANSFER MASUK',
                'transfer_out' => 'TRANSFER KELUAR',
                'adjustment' => 'PENYESUAIAN',
                default => strtoupper($movement->type),
            },
            'date' => $movement->created_at->format('d M Y H:i'),
            'staff' => $movement->creator?->name ?? '-',
            'location' => $movement->location->name,
            'buyer' => $movement->buyer?->name,
            'items' => match (true) {
                $related instanceof Sale => $related->items->map(fn ($i) => [
                    'product' => $i->product->name ?? 'Produk #'.$i->product_id,
                    'qty' => $i->qty,
                    'price' => (float) $i->price,
                    'subtotal' => (float) $i->subtotal,
                ])->toArray(),
                default => [],
            },
            'total' => $related instanceof Sale ? (float) $related->total : null,
            'paid_amount' => $related instanceof Sale ? (float) $related->paid_amount : null,
            'change' => $related instanceof Sale
                ? (float) ($related->paid_amount - $related->total)
                : null,
            'payment_method' => $related instanceof Sale ? $related->payment_method : null,
            'additional_costs' => $movement->additional_costs,
            'notes' => $movement->notes,
        ];
    }
}
