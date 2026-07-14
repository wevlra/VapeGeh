<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return ['product.category', 'location'];
    }

    public function title(): string
    {
        return 'Laporan Stok';
    }

    public function headings(): array
    {
        return ['Produk', 'Kategori', 'Lokasi', 'Jumlah'];
    }

    public function map($row): array
    {
        return [
            $row->product?->name ?? '-',
            $row->product?->category?->name ?? '-',
            $row->location?->name ?? '-',
            $row->qty,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
