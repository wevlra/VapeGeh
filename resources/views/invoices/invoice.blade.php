<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>
        @page { size: A4; margin: 20mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #333; }
        .header img { max-height: 60px; }
        .header .details { flex: 1; }
        .header .details h1 { font-size: 22px; margin-bottom: 2px; }
        .header .details p { font-size: 11px; color: #666; }
        .title-box { text-align: right; margin-bottom: 24px; }
        .title-box h2 { font-size: 28px; letter-spacing: 4px; text-transform: uppercase; color: #333; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 24px; font-size: 11px; }
        .meta div p { margin: 2px 0; }
        .meta .label { color: #999; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead th { background: #f5f5f5; padding: 8px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        thead th:last-child { text-align: right; }
        tbody td { padding: 8px 12px; border-bottom: 1px solid #eee; }
        tbody td:last-child { text-align: right; }
        tbody tr:nth-child(even) { background: #fafafa; }
        .totals { margin-left: auto; width: 300px; }
        .totals table { margin-bottom: 0; }
        .totals td { padding: 4px 12px; border: none; }
        .totals td:last-child { text-align: right; }
        .totals .grand td { font-size: 16px; font-weight: bold; border-top: 2px solid #333; padding-top: 8px; }
        .footer { margin-top: 48px; text-align: center; font-size: 11px; color: #999; border-top: 1px solid #eee; padding-top: 16px; }
        .signature { margin-top: 48px; }
        .signature p { margin: 2px 0; font-size: 11px; }
        .signature .line { margin-top: 40px; width: 200px; border-top: 1px solid #333; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
    </style>
</head>
<body>
    @php $location = $movement->location; @endphp

    <div class="header">
        <img src="{{ public_path('assets/images/logo-light-tr.png') }}" alt="{{ config('store.name') }}">
        <div class="details">
            <h1>{{ config('store.name') }}</h1>
            <p>{{ $location->address ?? config('store.address') }}</p>
            <p>{{ config('store.phone') }}</p>
        </div>
    </div>

    <div class="title-box">
        <h2>Invoice</h2>
    </div>

    @php
        $related = $movement->related;
        $invoiceNumber = $related instanceof \App\Models\Sale ? $related->invoice_number : 'SM-'.$movement->id;
    @endphp

    <div class="meta">
        <div>
            <p><span class="label">Invoice #</span> {{ $invoiceNumber }}</p>
            <p><span class="label">Date</span> {{ $movement->created_at->format('d M Y') }}</p>
            <p><span class="label">Cashier</span> {{ $movement->creator?->name ?? '-' }}</p>
            <p><span class="label">Location</span> {{ $location->name }}</p>
        </div>
        <div style="text-align: right;">
            @if ($related instanceof \App\Models\Sale)
            <p><span class="label">Payment</span> {{ match($related->payment_method) { 'qris' => 'QRIS', default => ucfirst($related->payment_method) } }}</p>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>Product</th>
                <th style="width:60px">Qty</th>
                <th style="width:100px">Price</th>
                <th style="width:120px">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @if ($related instanceof \App\Models\Sale)
                @foreach ($related->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->product->name ?? 'Product #'.$item->product_id }}</td>
                    <td>{{ $item->qty }}</td>
                    <td>Rp {{ number_format((float) $item->price, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td>1</td>
                    <td>{{ $movement->product->name }}</td>
                    <td>{{ abs((int) $movement->quantity) }}</td>
                    <td>Rp {{ number_format((float) ($movement->unit_price ?? 0), 0, ',', '.') }}</td>
                    <td>Rp {{ number_format(abs((int) $movement->quantity) * (float) ($movement->unit_price ?? 0), 0, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @if ($related instanceof \App\Models\Sale)
    <div class="totals">
        <table>
            <tr><td>Subtotal</td><td>Rp {{ number_format((float) $related->total, 0, ',', '.') }}</td></tr>
            <tr class="grand"><td>Total</td><td>Rp {{ number_format((float) $related->total, 0, ',', '.') }}</td></tr>
            @if ($related->paid_amount > 0)
            <tr><td>Paid</td><td>Rp {{ number_format((float) $related->paid_amount, 0, ',', '.') }}</td></tr>
                @if ($related->paid_amount > $related->total)
                <tr><td>Change</td><td>Rp {{ number_format((float) ($related->paid_amount - $related->total), 0, ',', '.') }}</td></tr>
                @endif
            @endif
        </table>
    </div>
    @endif

    @if ($movement->notes)
        <div style="margin-top: 16px; font-size: 11px;">
            <strong>Notes:</strong> {{ $movement->notes }}
        </div>
    @endif

    <div class="signature">
        <p>Thanks for your business,</p>
        <p><strong>{{ config('store.name') }}</strong></p>
        <div class="line"></div>
        <p>{{ $movement->creator?->name ?? '-' }}</p>
    </div>

    <div class="footer">
        {{ config('store.name') }} — {{ $location->address ?? config('store.address') }} — {{ config('store.phone') }}
    </div>
</body>
</html>
