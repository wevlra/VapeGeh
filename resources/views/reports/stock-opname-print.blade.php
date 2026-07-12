<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penyesuaian Stok - {{ $opname->opname_number }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
        .header { text-align: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 18px; font-weight: 700; margin-bottom: 2px; }
        .header .subtitle { font-size: 12px; color: #555; }
        .info { display: flex; flex-wrap: wrap; gap: 8px 24px; margin-bottom: 16px; font-size: 11px; }
        .info .item { display: flex; gap: 6px; }
        .info .label { color: #666; }
        .info .value { font-weight: 600; }
        .badge { display: inline-block; padding: 1px 8px; border-radius: 3px; font-size: 10px; font-weight: 600; }
        .badge-draft { background: #fef3c7; color: #92400e; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        thead th { background: #333; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
        thead th.right { text-align: right; }
        thead th.center { text-align: center; }
        tbody tr { border-bottom: 1px solid #e0e0e0; }
        tbody tr:nth-child(even) { background: #fafafa; }
        tbody td { padding: 5px 8px; font-size: 10px; vertical-align: middle; }
        tbody td.right { text-align: right; font-variant-numeric: tabular-nums; }
        tbody td.center { text-align: center; }
        .positive { color: #065f46; }
        .negative { color: #991b1b; }
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
        <h1>Laporan Penyesuaian Stok</h1>
        <div class="subtitle">{{ config('store.name') }} &mdash; {{ $opname->opname_number }}</div>
    </div>

    <div class="info">
        <div class="item">
            <span class="label">Lokasi:</span>
            <span class="value">{{ $opname->location->name }}</span>
        </div>
        <div class="item">
            <span class="label">Status:</span>
            <span class="badge badge-{{ $opname->status }}">{{ $opname->status === 'completed' ? 'Selesai' : 'Draft' }}</span>
        </div>
        <div class="item">
            <span class="label">Dibuat Oleh:</span>
            <span class="value">{{ $opname->creator->name }}</span>
        </div>
        <div class="item">
            <span class="label">Tanggal:</span>
            <span class="value">{{ $opname->created_at->format('d/m/Y H:i') }}</span>
        </div>
        @if ($opname->completed_at)
            <div class="item">
                <span class="label">Selesai Pada:</span>
                <span class="value">{{ $opname->completed_at->format('d/m/Y H:i') }}</span>
            </div>
        @endif
        @if ($opname->notes)
            <div class="item">
                <span class="label">Catatan:</span>
                <span class="value">{{ $opname->notes }}</span>
            </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Produk</th>
                <th class="center">Stok Sistem</th>
                <th class="center">Stok Fisik</th>
                <th class="center">Selisih</th>
                <th class="right">Selisih Nilai</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($opname->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td class="center">{{ number_format($item->system_qty, 0, ',', '.') }}</td>
                    <td class="center">{{ number_format($item->actual_qty, 0, ',', '.') }}</td>
                    <td class="center {{ $item->difference > 0 ? 'positive' : ($item->difference < 0 ? 'negative' : '') }}">
                        {{ $item->difference > 0 ? '+' : '' }}{{ number_format($item->difference, 0, ',', '.') }}
                    </td>
                    <td class="right {{ $item->difference_value > 0 ? 'positive' : ($item->difference_value < 0 ? 'negative' : '') }}">
                        {{ $item->difference_value > 0 ? '+' : '' }}Rp {{ number_format($item->difference_value, 0, ',', '.') }}
                    </td>
                    <td>{{ $item->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #999;">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($opname->items->count())
        @php
            $totalDifference = $opname->items->sum('difference');
            $totalDifferenceValue = $opname->total_difference_value;
        @endphp
        <div class="summary">
            <div>Total Produk: {{ number_format($opname->items->count(), 0, ',', '.') }}</div>
            <div>Total Selisih Unit: <span class="{{ $totalDifference > 0 ? 'positive' : ($totalDifference < 0 ? 'negative' : '') }}">{{ $totalDifference > 0 ? '+' : '' }}{{ number_format($totalDifference, 0, ',', '.') }}</span></div>
            <div>Total Selisih Nilai: <span class="{{ $totalDifferenceValue > 0 ? 'positive' : ($totalDifferenceValue < 0 ? 'negative' : '') }}">{{ $totalDifferenceValue > 0 ? '+' : '' }}Rp {{ number_format($totalDifferenceValue, 0, ',', '.') }}</span></div>
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
