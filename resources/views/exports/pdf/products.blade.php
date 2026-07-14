@extends('exports.pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>Produk</th>
                <th>Kategori</th>
                <th style="text-align: right;">Harga Beli</th>
                <th style="text-align: right;">Harga Jual</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
                <tr>
                    <td>{{ $record->name }}</td>
                    <td>
                        @php
                            $badgeClass = 'badge-gray';
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $record->category?->name ?? '-' }}</span>
                    </td>
                    <td style="text-align: right;">{{ number_format($record->purchase_price, 0, ',', '.') }}</td>
                    <td style="text-align: right;">{{ number_format($record->selling_price, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">Tidak ada data produk</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
