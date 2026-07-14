@extends('exports.pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Tipe</th>
                <th>Produk</th>
                <th>Lokasi</th>
                <th style="text-align: right;">Jumlah</th>
                <th>Staf</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
                <tr>
                    <td>{{ $record->created_at?->format('d/m/Y H:i') }}</td>
                    <td>
                        @php
                            $typeLabel = match($record->type) {
                                'in' => 'Stok Masuk',
                                'out' => 'Stok Keluar',
                                'transfer' => 'Transfer',
                                'opname' => 'Penyesuaian Stok',
                                'adjustment' => 'Penyesuaian',
                                default => ucfirst($record->type)
                            };
                            $badgeClass = match($record->type) {
                                'in' => 'badge-success',
                                'out' => 'badge-danger',
                                'transfer' => 'badge-info',
                                default => 'badge-gray'
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $typeLabel }}</span>
                    </td>
                    <td>
                        @php
                            $related = $record->related;
                            $productName = '-';
                            if ($related instanceof \App\Models\Sale || $related instanceof \App\Models\StockTransfer || $related instanceof \App\Models\StockEntry || $related instanceof \App\Models\StockOpname) {
                                $items = collect($related->items)->pluck('product.name')->filter()->toArray();
                                $count = count($items);
                                if ($count > 1) {
                                    $productName = $items[0] . ' + ' . ($count - 1) . ' lainnya';
                                } else {
                                    $productName = $items[0] ?? '-';
                                }
                            } else {
                                $productName = $record->product?->name ?? '-';
                            }
                        @endphp
                        {{ $productName }}
                    </td>
                    <td>{{ $record->location?->name ?? '-' }}</td>
                    <td style="text-align: right;">
                        @php
                            $total = 0;
                            if ($related instanceof \App\Models\Sale || $related instanceof \App\Models\StockTransfer || $related instanceof \App\Models\StockEntry) {
                                $total = collect($related->items)->sum('qty');
                            } elseif ($related instanceof \App\Models\StockOpname) {
                                $total = collect($related->items)->sum('difference');
                            } else {
                                $total = abs($record->quantity);
                            }
                            
                            $prefix = $total > 0 && (!($related instanceof \App\Models\StockOpname) || $total > 0) ? '+' : '';
                            $qtyStr = '';
                            if ($related instanceof \App\Models\StockOpname) {
                                $qtyStr = $total > 0 ? '+' . $total : (string) $total;
                            } else {
                                $qtyStr = $record->quantity > 0 ? '+' . $total : (string) (-$total);
                            }
                            $color = $record->quantity > 0 ? 'green' : 'red';
                        @endphp
                        <span style="color: {{ $color }}; font-weight: bold;">{{ $qtyStr }}</span>
                    </td>
                    <td>{{ $record->creator?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">Tidak ada data riwayat stok</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
