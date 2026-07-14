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

class StockTransfersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return ['fromLocation', 'toLocation', 'creator'];
    }

    public function title(): string
    {
        return 'Data Transfer Stok';
    }

    public function headings(): array
    {
        return ['No. Transfer', 'Dari Lokasi', 'Ke Lokasi', 'Status', 'Dibuat Oleh', 'Tanggal'];
    }

    public function map($row): array
    {
        $statusLabel = match ($row->status) {
            'pending' => 'Tertunda',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($row->status)
        };

        return [
            $row->transfer_number,
            $row->fromLocation?->name ?? '-',
            $row->toLocation?->name ?? '-',
            $statusLabel,
            $row->creator?->name ?? '-',
            $row->created_at?->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
