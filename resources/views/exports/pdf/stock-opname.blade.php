@extends('exports.pdf.layout')

@section('content')
    <div style="margin-bottom: 20px;">
        <table style="width: 50%; margin-bottom: 0; border: none;">
            <tr style="background: none;">
                <td style="border: none; padding: 2px 0;"><strong>Lokasi:</strong></td>
                <td style="border: none; padding: 2px 0;">{{ $opname->location->name }}</td>
            </tr>
            <tr style="background: none;">
                <td style="border: none; padding: 2px 0;"><strong>Status:</strong></td>
                <td style="border: none; padding: 2px 0;">
                    <span class="badge badge-{{ $opname->status === 'completed' ? 'success' : 'warning' }}">
                        {{ $opname->status === 'completed' ? 'Selesai' : 'Draft' }}
                    </span>
                </td>
            </tr>
            <tr style="background: none;">
                <td style="border: none; padding: 2px 0;"><strong>Dibuat Oleh:</strong></td>
                <td style="border: none; padding: 2px 0;">{{ $opname->creator->name }}</td>
            </tr>
            <tr style="background: none;">
                <td style="border: none; padding: 2px 0;"><strong>Tanggal:</strong></td>
                <td style="border: none; padding: 2px 0;">{{ $opname->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @if ($opname->completed_at)
                <tr style="background: none;">
                    <td style="border: none; padding: 2px 0;"><strong>Selesai Pada:</strong></td>
                    <td style="border: none; padding: 2px 0;">{{ $opname->completed_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endif
            @if ($opname->notes)
                <tr style="background: none;">
                    <td style="border: none; padding: 2px 0;"><strong>Catatan:</strong></td>
                    <td style="border: none; padding: 2px 0;">{{ $opname->notes }}</td>
                </tr>
            @endif
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Produk</th>
                <th style="text-align: center;">Stok Sistem</th>
                <th style="text-align: center;">Stok Fisik</th>
                <th style="text-align: center;">Selisih</th>
                <th style="text-align: right;">Selisih Nilai</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($opname->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td style="text-align: center;">{{ number_format($item->system_qty, 0, ',', '.') }}</td>
                    <td style="text-align: center;">{{ number_format($item->actual_qty, 0, ',', '.') }}</td>
                    <td style="text-align: center; color: {{ $item->difference > 0 ? '#166534' : ($item->difference < 0 ? '#991b1b' : 'inherit') }}">
                        {{ $item->difference > 0 ? '+' : '' }}{{ number_format($item->difference, 0, ',', '.') }}
                    </td>
                    <td style="text-align: right; color: {{ $item->difference_value > 0 ? '#166534' : ($item->difference_value < 0 ? '#991b1b' : 'inherit') }}">
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
        @if ($opname->items->count())
            @php
                $totalDifference = $opname->items->sum('difference');
                $totalDifferenceValue = $opname->total_difference_value;
            @endphp
            <tfoot>
                <tr class="total-row">
                    <td colspan="4" style="text-align: right;">Total Keseluruhan</td>
                    <td style="text-align: center; color: {{ $totalDifference > 0 ? '#166534' : ($totalDifference < 0 ? '#991b1b' : 'inherit') }}">
                        {{ $totalDifference > 0 ? '+' : '' }}{{ number_format($totalDifference, 0, ',', '.') }}
                    </td>
                    <td style="text-align: right; color: {{ $totalDifferenceValue > 0 ? '#166534' : ($totalDifferenceValue < 0 ? '#991b1b' : 'inherit') }}">
                        {{ $totalDifferenceValue > 0 ? '+' : '' }}Rp {{ number_format($totalDifferenceValue, 0, ',', '.') }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
