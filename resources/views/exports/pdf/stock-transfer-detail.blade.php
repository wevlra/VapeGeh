@extends('exports.pdf.layout')

@section('content')
    <div style="margin-bottom: 20px;">
        <table style="width: 50%; margin-bottom: 0; border: none;">
            <tr style="background: none;">
                <td style="border: none; padding: 2px 0;"><strong>No Transfer:</strong></td>
                <td style="border: none; padding: 2px 0;">{{ $transfer->transfer_number }}</td>
            </tr>
            <tr style="background: none;">
                <td style="border: none; padding: 2px 0;"><strong>Dari Lokasi:</strong></td>
                <td style="border: none; padding: 2px 0;">{{ $transfer->fromLocation->name ?? '-' }}</td>
            </tr>
            <tr style="background: none;">
                <td style="border: none; padding: 2px 0;"><strong>Ke Lokasi:</strong></td>
                <td style="border: none; padding: 2px 0;">{{ $transfer->toLocation->name ?? '-' }}</td>
            </tr>
            <tr style="background: none;">
                <td style="border: none; padding: 2px 0;"><strong>Dibuat Oleh:</strong></td>
                <td style="border: none; padding: 2px 0;">{{ $transfer->creator->name ?? '-' }}</td>
            </tr>
            <tr style="background: none;">
                <td style="border: none; padding: 2px 0;"><strong>Tanggal:</strong></td>
                <td style="border: none; padding: 2px 0;">{{ $transfer->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @if ($transfer->notes)
                <tr style="background: none;">
                    <td style="border: none; padding: 2px 0;"><strong>Catatan:</strong></td>
                    <td style="border: none; padding: 2px 0;">{{ $transfer->notes }}</td>
                </tr>
            @endif
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Produk</th>
                <th style="text-align: center;">Jumlah</th>
                <th style="text-align: right;">Harga Beli/th>
                <th style="text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; $totalValue = 0; @endphp
            @forelse ($transfer->items as $i => $item)
                @php
                    $qty = $item->qty;
                    $price = $item->product->purchase_price;
                    $subtotal = $qty * $price;
                    $totalQty += $qty;
                    $totalValue += $subtotal;
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td style="text-align: center;">{{ number_format($qty, 0, ',', '.') }}</td>
                    <td style="text-align: right;">Rp {{ number_format($price, 0, ',', '.') }}</td>
                    <td style="text-align: right;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px; color: #999;">Tidak ada data barang</td>
                </tr>
            @endforelse
        </tbody>
        @if ($transfer->items->count())
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" style="text-align: right;">Total Keseluruhan</td>
                    <td style="text-align: center;">{{ number_format($totalQty, 0, ',', '.') }}</td>
                    <td></td>
                    <td style="text-align: right;">Rp {{ number_format($totalValue, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
