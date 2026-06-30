<?php

namespace App\Http\Controllers;

use App\Actions\BuildReceiptPrintData;
use App\Models\StockMovement;

class ReceiptPrintDataController extends Controller
{
    public function __invoke(StockMovement $stockMovement, BuildReceiptPrintData $builder)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'staff']), 403);

        if (auth()->user()->role === 'staff' && $stockMovement->location_id !== auth()->user()->location_id) {
            abort(403);
        }

        return response()->json($builder->build($stockMovement));
    }
}
