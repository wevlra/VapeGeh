<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan - {{ config('store.name') }}</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
        .header { text-align: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 18px; font-weight: 700; margin-bottom: 2px; }
        .header .subtitle { font-size: 12px; color: #555; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 10px; color: #444; }
        .meta .filters span { background: #f0f0f0; padding: 2px 8px; border-radius: 3px; margin-left: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        thead th { background: #333; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
        thead th:nth-child(5) { text-align: right; }
        tbody tr { border-bottom: 1px solid #e0e0e0; }
        tbody tr:nth-child(even) { background: #fafafa; }
        tbody td { padding: 5px 8px; font-size: 10px; vertical-align: middle; }
        tbody td:nth-child(5) { text-align: right; font-variant-numeric: tabular-nums; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: 600; }
        .badge-pos { background: #d1fae5; color: #065f46; }
        .badge-stock_out { background: #fef3c7; color: #92400e; }
        .badge-cash { background: #d1fae5; color: #065f46; }
        .badge-transfer { background: #dbeafe; color: #1e40af; }
        .badge-qris { background: #e0e7ff; color: #3730a3; }
        .badge-default { background: #f3f4f6; color: #374151; }
        .summary { display: flex; gap: 24px; padding: 10px 0; border-top: 2px solid #333; font-size: 11px; font-weight: 600; }
        .footer { text-align: center; margin-top: 20px; font-size: 9px; color: #999; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
        .print-btn { position: fixed; top: 16px; right: 16px; padding: 8px 20px; background: #333; color: #fff; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; z-index: 100; }
        .print-btn:hover { background: #555; }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">🖨️ Cetak</button>

    <div class="header">
        <h1>Laporan Penjualan</h1>
        <div class="subtitle">{{ config('store.name') }} &mdash; Periode: {{ $periodLabel }}</div>
    </div>

    @if (count($filters))
        <div class="meta">
            <div class="filters">
                Filter aktif:
                @foreach ($filters as $label => $value)
                    <span>{{ $label }}: {{ $value }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tipe</th>
                <th>No. Invoice</th>
                <th>Lokasi</th>
                <th>Total</th>
                <th>Pembayaran</th>
                <th>Kasir</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $i => $record)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <span class="badge badge-{{ $record->source_type }}">
                            {{ $record->source_type === 'pos' ? 'POS' : 'Stok Keluar' }}
                        </span>
                    </td>
                    <td>{{ $record->reference ?: '-' }}</td>
                    <td>{{ $record->location_name }}</td>
                    <td>Rp {{ number_format((float) $record->total, 0, ',', '.') }}</td>
                    <td>
                        @php
                            $paymentLabel = match($record->payment_method) {
                                'qris' => 'QRIS',
                                'transfer' => 'Transfer',
                                'cash' => 'Tunai',
                                default => $record->payment_method ? ucfirst($record->payment_method) : '-',
                            };
                            $paymentClass = match($record->payment_method) {
                                'cash' => 'badge-cash',
                                'transfer' => 'badge-transfer',
                                'qris' => 'badge-qris',
                                default => 'badge-default',
                            };
                        @endphp
                        <span class="badge {{ $paymentClass }}">{{ $paymentLabel }}</span>
                    </td>
                    <td>{{ $record->kasir_name }}</td>
                    <td>{{ \Carbon\Carbon::parse($record->created_at)->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px; color: #999;">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($records->count())
        <div class="summary">
            <div>Total Transaksi: {{ number_format($records->count()) }}</div>
            <div>Total Pendapatan: Rp {{ number_format($records->sum('total'), 0, ',', '.') }}</div>
        </div>
    @endif

    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }} oleh {{ auth()->user()->name }}
    </div>

    <script>
        window.onload = function() { window.print(); }
    </script>
</body>
</html>
