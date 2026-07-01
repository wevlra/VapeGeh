<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\StockMovement;
use App\Traits\AuthorizesReceiptAccess;

class ReceiptController extends Controller
{
    use AuthorizesReceiptAccess;

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
}
