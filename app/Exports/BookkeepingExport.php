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

class BookkeepingExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(
        private readonly EloquentBuilder $query,
        private readonly string $tab,
    ) {}

    public function query()
    {
        return $this->query;
    }

    public function title(): string
    {
        return $this->tab === 'income' ? 'Pendapatan' : 'Pengeluaran';
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
            'service' => 'Jasa / Service',
            'cashback' => 'Cashback & Rebate',
            'opname' => 'Penyesuaian Stok',
            'investment' => 'Modal / Investasi',
            'purchase' => 'Pembelian',
            'salary' => 'Gaji',
            'utilities' => 'Utilitas',
            'transport' => 'Transportasi',
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
