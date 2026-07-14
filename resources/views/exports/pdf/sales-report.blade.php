@extends('exports.pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Tipe</th>
                <th>No. Invoice</th>
                <th>Lokasi</th>
                <th>Kasir</th>
                <th>Pembayaran</th>
                <th style="text-align: right;">Total (IDR)</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @forelse($records as $record)
                @php $grandTotal += $record->total; @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($record->created_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        @php
                            $typeLabel = match($record->source_type) {
                                'pos' => 'POS',
                                'stock_out' => 'Stok Keluar',
                                default => ucfirst($record->source_type)
                            };
                            $typeClass = match($record->source_type) {
                                'pos' => 'badge-success',
                                'stock_out' => 'badge-warning',
                                default => 'badge-gray'
                            };
                        @endphp
                        <span class="badge {{ $typeClass }}">{{ $typeLabel }}</span>
                    </td>
                    <td>{{ $record->reference ?: '-' }}</td>
                    <td>{{ $record->location_name }}</td>
                    <td>{{ $record->kasir_name }}</td>
                    <td>
                        @php
                            $payLabel = match($record->payment_method) {
                                'qris' => 'QRIS',
                                'transfer' => 'Transfer',
                                'cash' => 'Tunai',
                                default => $record->payment_method ? ucfirst($record->payment_method) : '-'
                            };
                        @endphp
                        {{ $payLabel }}
                    </td>
                    <td style="text-align: right;">{{ number_format($record->total, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">Tidak ada data pada periode ini</td>
                </tr>
            @endforelse
            @if($records->count() > 0)
                <tr class="total-row">
                    <td colspan="6" style="text-align: right;">TOTAL:</td>
                    <td style="text-align: right;">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>
@endsection
