<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Services\InvoiceService;

class InvoiceController extends Controller
{
    public function __invoke(StockMovement $stockMovement, InvoiceService $invoiceService)
    {
        $this->authorizeAccess($stockMovement);

        abort_if($stockMovement->type !== 'out', 404);

        $invoice = $invoiceService->generate($stockMovement);

        return $invoice->stream();
    }

    private function authorizeAccess(StockMovement $stockMovement): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'staff']), 403);

        if (auth()->user()->role === 'staff' && $stockMovement->location_id !== auth()->user()->location_id) {
            abort(403);
        }
    }
}
