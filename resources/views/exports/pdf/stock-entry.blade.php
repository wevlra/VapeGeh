@extends('exports.pdf.layout')

@section('content')
    <div style="margin-bottom: 20px;">
        <table style="width: 50%; margin-bottom: 0; border: none;">
            <tr style="background: none;">
                <td style="border: none; padding: 2px 0;"><strong>Lokasi:</strong></td>
                <td style="border: none; padding: 2px 0;">{{ $entry->location->name ?? '-' }}</td>
            </tr>
            <tr style="background: none;">
                <td style="border: none; padding: 2px 0;"><strong>Vendor:</strong></td>
                <td style="border: none; padding: 2px 0;">{{ $entry->vendor->name ?? '-' }}</td>
            </tr>
            <tr style="background: none;">
                <td style="border: none; padding: 2px 0;"><strong>Dibuat Oleh:</strong></td>
                <td style="border: none; padding: 2px 0;">{{ $entry->creator->name ?? '-' }}</td>
            </tr>
            <tr style="background: none;">
                <td style="border: none; padding: 2px 0;"><strong>Tanggal:</strong></td>
                <td style="border: none; padding: 2px 0;">{{ $entry->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @if ($entry->notes)
                <tr style="background: none;">
                    <td style="border: none; padding: 2px 0;"><strong>Catatan:</strong></td>
                    <td style="border: none; padding: 2px 0;">{{ $entry->notes }}</td>
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
                <th style="text-align: right;">Harga Satuan</th>
                <th style="text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; $totalValue = 0; @endphp
            @forelse ($entry->items as $i => $item)
                @php
                    $qty = $item->qty;
                    $price = $item->unit_price;
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
            
            @if(is_array($entry->additional_costs) && count($entry->additional_costs) > 0)
                <tr>
                    <td colspan="5" style="background-color: #f3f4f6; font-weight: bold;">Biaya Tambahan</td>
                </tr>
                @foreach($entry->additional_costs as $cost)
                    @php $totalValue += ($cost['amount'] ?? 0); @endphp
                    <tr>
                        <td colspan="4">{{ $cost['description'] ?? 'Biaya Lainnya' }}</td>
                        <td style="text-align: right;">Rp {{ number_format($cost['amount'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
        @if ($entry->items->count())
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
