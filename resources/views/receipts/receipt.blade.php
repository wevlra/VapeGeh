<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota</title>
    @php
        $paperSize = request()->query('size', '58mm');
        if (!in_array($paperSize, ['58mm', '80mm'])) {
            $paperSize = '58mm';
        }
    @endphp
    <style>
        @page { size: {{ $paperSize }} auto; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 11px; width: {{ $paperSize }}; padding: 4px 4px 20px; color: #000; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 8px; }
        .header img { max-height: 48px; }
        .header .name { font-size: 14px; font-weight: bold; }
        .header .tagline { font-size: 10px; font-style: italic; margin-bottom: 2px; }
        .header .info { font-size: 9px; }
        hr { border: none; border-top: 1px dashed #333; margin: 6px 0; }
        .ref { font-weight: bold; font-size: 11px; margin-bottom: 2px; }
        .meta { font-size: 9px; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        thead th { padding: 3px 0 6px; text-align: left; }
        thead th:last-child { text-align: right; }
        .th-divider td { height: 1px; background: #333; padding: 0; font-size: 0; line-height: 0; }
        tbody td { padding: 2px 0; vertical-align: top; word-break: break-word; }
        tbody td:last-child { text-align: right; white-space: nowrap; }
        .totals { margin-top: 6px; text-align: right; font-size: 10px; }
        .totals .grand-total { font-size: 12px; font-weight: bold; margin-top: 2px; }
        .footer { text-align: center; margin-top: 12px; font-size: 9px; }
        .payment { margin-top: 2px; font-size: 10px; }
        .additional { margin-top: 6px; font-size: 9px; }
        .additional li { list-style: none; }
    </style>
</head>
<body>
    @php $location = $movement->location; @endphp
    <div class="header">
        <img src="{{ asset('assets/images/logo-stacked-dark-tr.png') }}" alt="{{ config('store.name') }}">
        {{-- <div class="name">{{ config('store.name') }}</div> --}}
        @if(config('store.tagline'))
            <div class="tagline">{{ config('store.tagline') }}</div>
        @endif
        <div class="info">{{ $location->address ?? config('store.address') }}</div>
        <div class="info">{{ config('store.phone') }}</div>
    </div>

    <hr>

    @php
        $related = $movement->related;
        $isLocationDeletion = $related === null && $movement->related_type === \App\Models\Location::class;
        $refNumber = match (true) {
            $related instanceof \App\Models\Sale => $related->invoice_number,
            $related instanceof \App\Models\StockTransfer => $related->transfer_number,
            default => '#SM-'.$movement->id,
        };
        $typeLabel = match ($movement->type) {
            'in' => 'STOK MASUK',
            'out' => 'STOK KELUAR',
            'transfer_in' => 'TRANSFER MASUK',
            'transfer_out' => 'TRANSFER KELUAR',
            'adjustment' => 'PENYESUAIAN',
            default => strtoupper($movement->type),
        };
    @endphp

    <div class="ref">#{{ $refNumber }}</div>
    <div class="meta">
        {{ $typeLabel }} &middot; {{ $movement->created_at->format('d M Y H:i') }}<br>
        Staf: {{ $movement->creator?->name ?? '-' }}<br>
        Lokasi: {{ $location->name }}
        @if ($movement->buyer)
            <br>Pembeli: {{ $movement->buyer->name }}
        @endif
    </div>

    <hr>

    @if ($related instanceof \App\Models\Sale)
        <table>
            <thead>
                <tr><th>Item</th><th>Jumlah</th><th>Harga</th><th>Subtotal</th></tr>
                <tr class="th-divider"><td colspan="4"></td></tr>
            </thead>
            <tbody>
                @foreach ($related->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'Produk #'.$item->product_id }}</td>
                    <td>{{ $item->qty }}</td>
                    <td>{{ number_format((float) $item->price, 0, ',', '.') }}</td>
                    <td>{{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="totals">
            <div>Total: Rp {{ number_format((float) $related->total, 0, ',', '.') }}</div>
            @if ($related->paid_amount > 0 && $related->paid_amount != $related->total)
                <div>Bayar: Rp {{ number_format((float) $related->paid_amount, 0, ',', '.') }}</div>
                <div>Kembali: Rp {{ number_format((float) ($related->paid_amount - $related->total), 0, ',', '.') }}</div>
            @endif
            <div class="payment">Metode: {{ match($related->payment_method) { 'qris' => 'QRIS', default => ucfirst($related->payment_method) } }}</div>
        </div>
    @elseif ($related instanceof \App\Models\StockTransfer)
        <table>
            <thead>
                <tr><th>Item</th><th>Jumlah</th></tr>
                <tr class="th-divider"><td colspan="2"></td></tr>
            </thead>
            <tbody>
                @foreach ($related->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'Produk #'.$item->product_id }}</td>
                    <td>{{ $item->qty }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top: 6px; font-size: 10px;">
            Lokasi Tujuan: {{ $related->toLocation?->name }}
        </div>
    @elseif ($related instanceof \App\Models\StockEntry)
        @php $stockIn = $related->type === 'in'; @endphp
        <table>
            <thead>
                <tr><th>Item</th><th>Jumlah</th><th>Harga</th></tr>
                <tr class="th-divider"><td colspan="3"></td></tr>
            </thead>
            <tbody>
                @foreach ($related->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'Produk #'.$item->product_id }}</td>
                    <td>{{ $item->qty }}</td>
                    <td>{{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="totals">
            <div>Total: Rp {{ number_format($related->items->sum(fn ($i) => $i->qty * (float) $i->unit_price), 0, ',', '.') }}</div>
            @if (! $stockIn && $related->buyer)
                <div>Pembeli: {{ $related->buyer->name }}</div>
            @endif
            @if ($stockIn && $related->vendor)
                <div>Vendor: {{ $related->vendor->name }}</div>
            @endif
        </div>
    @elseif ($isLocationDeletion)
        @php $siblings = $movement->sibling_movements; @endphp
        <table>
            <thead>
                <tr><th>Item</th><th>Jumlah</th></tr>
                <tr class="th-divider"><td colspan="2"></td></tr>
            </thead>
            <tbody>
                @foreach ($siblings as $sm)
                <tr>
                    <td>{{ $sm->product->name ?? 'Produk #'.$sm->product_id }}</td>
                    <td>{{ abs((int) $sm->quantity) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top: 6px; font-size: 10px;">Pemindahan stok otomatis saat lokasi dihapus.</div>
    @else
        <table>
            <thead>
                <tr><th>Item</th><th>Jumlah</th></tr>
                <tr class="th-divider"><td colspan="2"></td></tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $movement->product->name }}</td>
                    <td>{{ abs((int) $movement->quantity) }}</td>
                </tr>
            </tbody>
        </table>
        @if ($movement->unit_price)
            <div class="totals">Harga Satuan: Rp {{ number_format((float) $movement->unit_price, 0, ',', '.') }}</div>
        @endif
    @endif

    @if ($movement->additional_costs && count($movement->additional_costs))
        <div class="additional">
            <strong>Biaya Tambahan:</strong>
            <ul>
                @foreach ($movement->additional_costs as $cost)
                    <li>{{ $cost['description'] ?? '' }}: Rp {{ number_format((float) ($cost['amount'] ?? 0), 0, ',', '.') }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($movement->notes)
        <div class="additional">Catatan: {{ $movement->notes }}</div>
    @endif

    <hr>

    <div class="footer">
        {!! nl2br(e(config('store.receipt_footer'))) !!}
    </div>

    <script>
        if (!new URLSearchParams(location.search).has('noprint')) {
            window.onload = function() { window.print(); }
            window.onafterprint = function() { window.close(); }
        }
    </script>
</body>
</html>
