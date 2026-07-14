@extends('exports.pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Lokasi</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th style="text-align: right;">Jumlah (IDR)</th>
                <th>Dibuat Oleh</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAmount = 0; @endphp
            @forelse($records as $record)
                @php $totalAmount += $record->amount; @endphp
                <tr>
                    <td>{{ $record->date?->format('d/m/Y') }}</td>
                    <td>{{ $record->location?->name ?? '-' }}</td>
                    <td>
                        @php
                            $catStr = $record->category;
                            $catLabel = match($catStr) {
                                'sale' => 'Penjualan',
                                'debt_payment' => 'Pembayaran Hutang',
                                'purchase' => 'Pembelian',
                                'salary' => 'Gaji',
                                'utilities' => 'Utilitas',
                                'transport' => 'Transportasi',
                                'other' => 'Lainnya',
                                default => ucfirst($catStr)
                            };
                            $badgeClass = match($catStr) {
                                'sale' => 'badge-success',
                                'debt_payment' => 'badge-info',
                                'purchase' => 'badge-warning',
                                'salary' => 'badge-primary',
                                'utilities' => 'badge-info',
                                default => 'badge-gray'
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $catLabel }}</span>
                    </td>
                    <td>{{ $record->description }}</td>
                    <td style="text-align: right;">{{ number_format($record->amount, 0, ',', '.') }}</td>
                    <td>{{ $record->creator?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">Tidak ada data pada periode ini</td>
                </tr>
            @endforelse
            @if($records->count() > 0)
                <tr class="total-row">
                    <td colspan="4" style="text-align: right;">TOTAL:</td>
                    <td style="text-align: right;">{{ number_format($totalAmount, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>
@endsection
