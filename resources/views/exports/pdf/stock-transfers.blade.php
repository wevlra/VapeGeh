@extends('exports.pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No. Transfer</th>
                <th>Dari Lokasi</th>
                <th>Ke Lokasi</th>
                <th>Status</th>
                <th>Dibuat Oleh</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
                <tr>
                    <td>{{ $record->transfer_number }}</td>
                    <td>{{ $record->fromLocation?->name ?? '-' }}</td>
                    <td>{{ $record->toLocation?->name ?? '-' }}</td>
                    <td>
                        @php
                            $statusLabel = match($record->status) {
                                'pending' => 'Tertunda',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                                default => ucfirst($record->status)
                            };
                            $badgeClass = match($record->status) {
                                'pending' => 'badge-warning',
                                'completed' => 'badge-success',
                                'cancelled' => 'badge-danger',
                                default => 'badge-gray'
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td>{{ $record->creator?->name ?? '-' }}</td>
                    <td>{{ $record->created_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">Tidak ada data transfer stok</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
