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

        return view('receipts.receipt', ['movement' => $stockMovement]);
    }

    public function sale(Sale $sale)
    {
        $movement = $sale->stockMovements()->firstOrFail();

        $this->authorizeAccess($movement);

        $movement->load(['product', 'location', 'creator', 'buyer', 'related']);

        return view('receipts.receipt', ['movement' => $movement]);
    }

    private function authorizeAccess(StockMovement $stockMovement): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'staff']), 403);

        if (auth()->user()->role === 'staff' && $stockMovement->location_id !== auth()->user()->location_id) {
            abort(403);
        }
    }
}
