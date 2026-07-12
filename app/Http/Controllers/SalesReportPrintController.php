<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesReportPrintController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $salesQ = DB::table('sales')
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->join('locations', 'locations.id', '=', 'sales.location_id')
            ->select([
                DB::raw("'pos' as source_type"),
                'sales.id',
                'sales.invoice_number as reference',
                'locations.name as location_name',
                'users.name as kasir_name',
                'sales.total',
                'sales.payment_method',
                'sales.created_at',
            ]);

        $stockQ = DB::table('stock_entries')
            ->join('locations', 'locations.id', '=', 'stock_entries.location_id')
            ->join('users', 'users.id', '=', 'stock_entries.created_by')
            ->leftJoinSub(
                DB::table('stock_entry_items')
                    ->select('stock_entry_id', DB::raw('SUM(qty * unit_price) as total'))
                    ->groupBy('stock_entry_id'),
                'totals', fn ($j) => $j->on('totals.stock_entry_id', '=', 'stock_entries.id')
            )
            ->where('stock_entries.type', 'out')
            ->select([
                DB::raw("'stock_out' as source_type"),
                'stock_entries.id',
                DB::raw("'' as reference"),
                'locations.name as location_name',
                'users.name as kasir_name',
                DB::raw('COALESCE(totals.total, 0) as total'),
                'stock_entries.payment_method',
                'stock_entries.created_at',
            ]);

        $query = DB::query()->fromSub($salesQ->union($stockQ), 'combined');

        $periodStart = $this->getPeriodStart($request->query('period', 'all'));
        if ($periodStart) {
            $query->where('created_at', '>=', $periodStart);
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->query('source_type'));
        }

        if ($request->filled('location_name')) {
            $query->where('location_name', $request->query('location_name'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->query('payment_method'));
        }

        $records = $query->orderBy('created_at', 'desc')->get();

        $periodLabel = match ($request->query('period', 'all')) {
            'today' => 'Hari Ini',
            'week' => '7 Hari Terakhir',
            'month' => '30 Hari Terakhir',
            default => 'Semua Waktu',
        };

        return view('reports.sales-print', [
            'records' => $records,
            'periodLabel' => $periodLabel,
            'filters' => array_filter([
                'Lokasi' => $request->query('location_name'),
                'Tipe' => match ($request->query('source_type')) {
                    'pos' => 'POS',
                    'stock_out' => 'Stok Keluar',
                    default => null,
                },
                'Pembayaran' => match ($request->query('payment_method')) {
                    'cash' => 'Tunai',
                    'transfer' => 'Transfer',
                    'qris' => 'QRIS',
                    'other' => 'Lainnya',
                    default => null,
                },
            ]),
        ]);
    }

    private function getPeriodStart(string $period): ?string
    {
        return match ($period) {
            'today' => now()->startOfDay()->toDateTimeString(),
            'week' => now()->subDays(7)->startOfDay()->toDateTimeString(),
            'month' => now()->subDays(30)->startOfDay()->toDateTimeString(),
            default => null,
        };
    }
}
