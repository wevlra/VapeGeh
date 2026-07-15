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

class ProductsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return ['category'];
    }

    public function title(): string
    {
        return 'Data Produk';
    }

    public function headings(): array
    {
        return ['Nama Produk', 'Kategori', 'Harga Beli', 'Harga Jual', 'Harga Reseller', 'Harga 1', 'Harga 2'];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->category?->name ?? '-',
            $row->purchase_price,
            $row->selling_price,
            $row->reseller_price,
            $row->price_1,
            $row->price_2,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
