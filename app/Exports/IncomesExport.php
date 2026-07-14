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

class IncomesExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return ['location', 'creator'];
    }

    public function title(): string
    {
        return 'Data Pendapatan';
    }

    public function headings(): array
    {
        return ['Tanggal', 'Lokasi', 'Kategori', 'Deskripsi', 'Jumlah (IDR)', 'Dibuat Oleh'];
    }

    public function map($row): array
    {
        $catStr = $row->category;
        $catLabel = match ($catStr) {
            'sale' => 'Penjualan',
            'debt_payment' => 'Pembayaran Hutang',
            'other' => 'Lainnya',
            default => ucfirst($catStr)
        };

        return [
            $row->date?->format('d/m/Y'),
            $row->location?->name,
            $catLabel,
            $row->description,
            $row->amount,
            $row->creator?->name,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
