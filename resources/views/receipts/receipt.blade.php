<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; padding: 8px 12px; color: #000; }
        .header { text-align: center; margin-bottom: 12px; }
        .header img { max-height: 50px; margin-bottom: 6px; }
        .header .name { font-size: 16px; font-weight: bold; }
        .header .info { font-size: 10px; }
        hr { border: none; border-top: 1px dashed #333; margin: 8px 0; }
        .ref { font-weight: bold; font-size: 13px; margin-bottom: 4px; }
        .meta { font-size: 10px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        thead th { border-bottom: 1px solid #333; padding: 4px 0; text-align: left; }
        thead th:last-child { text-align: right; }
        tbody td { padding: 2px 0; vertical-align: top; }
        tbody td:last-child { text-align: right; white-space: nowrap; }
        .totals { margin-top: 8px; text-align: right; font-size: 11px; }
        .totals .grand-total { font-size: 14px; font-weight: bold; margin-top: 4px; }
        .footer { text-align: center; margin-top: 16px; font-size: 10px; }
        .payment { margin-top: 4px; font-size: 11px; }
        .additional { margin-top: 8px; font-size: 10px; }
        .additional li { list-style: none; }
    </style>
</head>
<body>
    @php $location = $movement->location; @endphp
    <div class="header">
        <img src="{{ public_path('assets/images/logo-light-tr.png') }}" alt="{{ config('store.name') }}">
        <div class="name">{{ config('store.name') }}</div>
        <div class="info">{{ $location->address ?? config('store.address') }}</div>
        <div class="info">{{ config('store.phone') }}</div>
    </div>

    <hr>

    @php
        $related = $movement->related;
        $refNumber = match (true) {
            $related instanceof \App\Models\Sale => $related->invoice_number,
            $related instanceof \App\Models\StockTransfer => $related->transfer_number,
            default => '#SM-'.$movement->id,
        };
        $typeLabel = match ($movement->type) {
            'in' => 'STOCK IN',
            'out' => 'STOCK OUT',
            'transfer_in' => 'TRANSFER IN',
            'transfer_out' => 'TRANSFER OUT',
            'adjustment' => 'ADJUSTMENT',
            default => strtoupper($movement->type),
        };
    @endphp

    <div class="ref">#{{ $refNumber }}</div>
    <div class="meta">
        {{ $typeLabel }} &middot; {{ $movement->created_at->format('d M Y H:i') }}<br>
        Staff: {{ $movement->creator?->name ?? '-' }}<br>
        Location: {{ $location->name }}
        @if ($movement->buyer)
            <br>Buyer: {{ $movement->buyer->name }}
        @endif
    </div>

    <hr>

    @if ($related instanceof \App\Models\Sale)
        <table>
            <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
            <tbody>
                @foreach ($related->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'Product #'.$item->product_id }}</td>
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
            <thead><tr><th>Item</th><th>Qty</th></tr></thead>
            <tbody>
                @foreach ($related->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'Product #'.$item->product_id }}</td>
                    <td>{{ $item->qty }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top: 6px; font-size: 10px;">
            From: {{ $related->fromLocation?->name }}<br>
            To: {{ $related->toLocation?->name }}
        </div>
    @else
        <table>
            <thead><tr><th>Item</th><th>Qty</th></tr></thead>
            <tbody>
                <tr>
                    <td>{{ $movement->product->name }}</td>
                    <td>{{ abs((int) $movement->quantity) }}</td>
                </tr>
            </tbody>
        </table>
        @if ($movement->unit_price)
            <div class="totals">Unit Price: Rp {{ number_format((float) $movement->unit_price, 0, ',', '.') }}</div>
        @endif
    @endif

    @if ($movement->additional_costs && count($movement->additional_costs))
        <div class="additional">
            <strong>Additional Costs:</strong>
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
        Terima kasih!<br>
        Barang yang sudah dibeli tidak dapat dikembalikan.
    </div>

    <script>
        window.onload = function() { window.print(); window.close(); }
    </script>
</body>
</html>
