<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(
        private readonly EloquentBuilder $query
    ) {}

    public function query()
    {
        return $this->query;
    }

    public function title(): string
    {
        return 'Laporan Penjualan';
    }

    public function headings(): array
    {
        return ['Tanggal', 'Tipe', 'No. Invoice', 'Lokasi', 'Kasir', 'Pembayaran', 'Total (IDR)'];
    }

    public function map($row): array
    {
        $typeLabel = match ($row->source_type) {
            'pos' => 'POS',
            'stock_out' => 'Stok Keluar',
            default => ucfirst($row->source_type)
        };

        $payLabel = match ($row->payment_method) {
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
            'cash' => 'Tunai',
            default => $row->payment_method ? ucfirst($row->payment_method) : '-'
        };

        $extraCosts = StockEntry::parseAdditionalCostsTotal($row->additional_costs ?? null);
        $finalTotal = (float) $row->total + $extraCosts;

        return [
            Carbon::parse($row->created_at)->format('d/m/Y H:i'),
            $typeLabel,
            $row->reference ?: '-',
            $row->location_name,
            $row->kasir_name,
            $payLabel,
            $finalTotal,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
