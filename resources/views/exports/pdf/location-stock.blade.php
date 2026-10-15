@extends('exports.pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>Produk</th>
                <th>Kategori</th>
                <th style="text-align: right;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @php $totalStock = 0; @endphp
            @forelse($records as $record)
                @php $totalStock += $record->qty; @endphp
                <tr>
                    <td>{{ $record->product?->name ?? '-' }}</td>
                    <td>{{ $record->product?->category?->name ?? '-' }}</td>
                    <td style="text-align: right;">
                        @php
                            $badgeClass = match(true) {
                                $record->qty <= 0 => 'badge-danger',
                                $record->qty < 10 => 'badge-warning',
                                default => 'badge-success'
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $record->qty }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">Tidak ada data stok pada lokasi ini</td>
                </tr>
            @endforelse
            @if($records->count() > 0)
                <tr class="total-row">
                    <td colspan="2" style="text-align: right;">TOTAL STOK:</td>
                    <td style="text-align: right;">{{ $totalStock }}</td>
                </tr>
            @endif
        </tbody>
    </table>
@endsection
