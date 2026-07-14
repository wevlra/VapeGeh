<?php

namespace App\Exports;

use App\Models\Sale;
use App\Models\StockEntry;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HistoryExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(
        private readonly EloquentBuilder $query
    ) {}

    public function query()
    {
        return $this->query;
    }

    public static function getEagerLoads(): array
    {
        return ['product', 'location', 'creator', 'related.items.product'];
    }

    public function title(): string
    {
        return 'Riwayat Stok';
    }

    public function headings(): array
    {
        return ['Tanggal', 'Tipe', 'Produk', 'Lokasi', 'Jumlah', 'Staf', 'Catatan'];
    }

    public function map($row): array
    {
        $typeLabel = match ($row->type) {
            'in' => 'Stok Masuk',
            'out' => 'Stok Keluar',
            'transfer' => 'Transfer',
            'opname' => 'Penyesuaian Stok',
            'adjustment' => 'Penyesuaian',
            default => ucfirst($row->type)
        };

        $related = $row->related;
        $productName = '-';
        if ($related instanceof Sale || $related instanceof StockTransfer || $related instanceof StockEntry || $related instanceof StockOpname) {
            $items = collect($related->items)->pluck('product.name')->filter()->toArray();
            $count = count($items);
            if ($count > 1) {
                $productName = $items[0].' + '.($count - 1).' lainnya';
            } else {
                $productName = $items[0] ?? '-';
            }
        } else {
            $productName = $row->product?->name ?? '-';
        }

        $total = 0;
        if ($related instanceof Sale || $related instanceof StockTransfer || $related instanceof StockEntry) {
            $total = collect($related->items)->sum('qty');
        } elseif ($related instanceof StockOpname) {
            $total = collect($related->items)->sum('difference');
        } else {
            $total = abs($row->quantity);
        }

        $qtyStr = '';
        if ($related instanceof StockOpname) {
            $qtyStr = $total > 0 ? '+'.$total : (string) $total;
        } else {
            $qtyStr = $row->quantity > 0 ? '+'.$total : (string) (-$total);
        }

        return [
            $row->created_at?->format('d/m/Y H:i'),
            $typeLabel,
            $productName,
            $row->location?->name ?? '-',
            $qtyStr,
            $row->creator?->name ?? '-',
            $row->notes ?? $row->related?->notes ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
