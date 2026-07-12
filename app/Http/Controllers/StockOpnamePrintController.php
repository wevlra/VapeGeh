<?php

namespace App\Http\Controllers;

use App\Models\StockOpname;
use Illuminate\View\View;

class StockOpnamePrintController extends Controller
{
    public function __invoke(StockOpname $stockOpname): View
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $stockOpname->load(['items.product', 'location', 'creator']);

        return view('reports.stock-opname-print', [
            'opname' => $stockOpname,
        ]);
    }
}
