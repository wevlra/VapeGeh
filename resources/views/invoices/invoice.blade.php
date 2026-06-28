<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>
        @page { size: A4; margin: 24mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.5;
            background: #fff;
        }

        /* ── Header ── */
        .header {
            width: 100%;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 3px solid #d97706;
        }
        .header td { vertical-align: top; padding: 0; }
        .header .logo-cell { width: 50%; }
        .header .logo-cell img { max-height: 48px; margin-bottom: 6px; }
        .header .company-name { font-size: 18px; font-weight: bold; color: #0f172a; margin-bottom: 2px; }
        .header .company-detail { font-size: 10px; color: #64748b; line-height: 1.6; }
        .header .title-cell { width: 50%; text-align: right; }
        .header .invoice-title { font-size: 28px; font-weight: bold; color: #d97706; letter-spacing: 3px; text-transform: uppercase; }
        .header .invoice-meta { font-size: 10px; color: #64748b; margin-top: 8px; line-height: 1.8; }
        .header .invoice-meta strong { color: #1e293b; }

        /* ── Status Badge ── */
        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 6px;
        }
        .status-paid { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .status-unpaid { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }

        /* ── Bill From / Bill To ── */
        .addresses { width: 100%; margin-bottom: 24px; }
        .addresses td { width: 50%; vertical-align: top; padding: 16px 20px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .addresses td:first-child { border-right: none; border-radius: 6px 0 0 6px; }
        .addresses td:last-child { border-radius: 0 6px 6px 0; }
        .addresses .label { font-size: 9px; font-weight: bold; color: #d97706; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px; }
        .addresses .name { font-size: 13px; font-weight: bold; color: #0f172a; margin-bottom: 4px; }
        .addresses .detail { font-size: 10px; color: #64748b; line-height: 1.7; }

        /* ── Items Table ── */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table thead th {
            background: #d97706;
            color: #fff;
            padding: 10px 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: left;
        }
        .items-table thead th:first-child { border-radius: 6px 0 0 0; }
        .items-table thead th:last-child { border-radius: 0 6px 0 0; text-align: right; }
        .items-table thead th.num { text-align: center; width: 36px; }
        .items-table thead th.qty { text-align: center; width: 50px; }
        .items-table thead th.price { text-align: right; width: 110px; }
        .items-table thead th.amount { text-align: right; width: 120px; }
        .items-table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
            color: #334155;
        }
        .items-table tbody td.num { text-align: center; color: #94a3b8; font-weight: bold; }
        .items-table tbody td.qty { text-align: center; }
        .items-table tbody td.price, .items-table tbody td.amount { text-align: right; }
        .items-table tbody tr:nth-child(even) td { background: #fefce8; }
        .items-table tbody tr:last-child td { border-bottom: 2px solid #e2e8f0; }

        /* ── Totals ── */
        .totals-section { width: 100%; margin-bottom: 24px; }
        .totals-section td { padding: 0; vertical-align: top; }
        .totals-section .spacer { width: 55%; }
        .totals-section .totals-box { width: 45%; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 6px 12px; font-size: 11px; color: #64748b; }
        .totals-table td:last-child { text-align: right; font-weight: 500; color: #1e293b; }
        .totals-table .total-row td {
            padding: 10px 12px;
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            border-top: 2px solid #d97706;
            background: #fefce8;
        }
        .totals-table .total-row td:last-child { color: #d97706; }

        /* ── Payment Info ── */
        .payment-section {
            width: 100%;
            margin-bottom: 28px;
            padding: 16px 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .payment-section .label { font-size: 9px; font-weight: bold; color: #d97706; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px; }
        .payment-section table { width: 100%; border-collapse: collapse; }
        .payment-section td { padding: 3px 0; font-size: 10px; color: #64748b; }
        .payment-section td:first-child { width: 120px; font-weight: 600; color: #475569; }

        /* ── Notes ── */
        .notes {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 24px;
            padding: 12px 16px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 6px;
        }
        .notes strong { color: #92400e; }

        /* ── Signature ── */
        .signature-section { width: 100%; margin-top: 32px; }
        .signature-section td { vertical-align: top; padding: 0; width: 50%; }
        .signature-section .thanks { font-size: 11px; color: #64748b; line-height: 1.6; }
        .signature-section .thanks strong { color: #0f172a; }
        .signature-section .sig-line { margin-top: 48px; width: 180px; border-top: 1px solid #cbd5e1; padding-top: 6px; font-size: 10px; color: #94a3b8; text-align: center; }

        /* ── Footer ── */
        .footer {
            margin-top: 32px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    @php
        $location = $movement->location;
        $related = $movement->related;
        $invoiceNumber = $related instanceof \App\Models\Sale ? $related->invoice_number : 'SM-'.$movement->id;
        $isSale = $related instanceof \App\Models\Sale;
        $isPaid = $isSale && $related->paid_amount >= $related->total;
    @endphp

    {{-- Header --}}
    <table class="header">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('assets/images/logo-light-tr.png') }}" alt="{{ config('store.name') }}">
                <div class="company-name">{{ config('store.name') }}</div>
                <div class="company-detail">
                    {{ $location->address ?? config('store.address') }}<br>
                    {{ config('store.phone') }}
                </div>
            </td>
            <td class="title-cell">
                <div class="invoice-title">Invoice</div>
                <div class="invoice-meta">
                    <strong>Invoice #:</strong> {{ $invoiceNumber }}<br>
                    <strong>Date:</strong> {{ $movement->created_at->format('d M Y') }}<br>
                    <strong>Cashier:</strong> {{ $movement->creator?->name ?? '-' }}<br>
                    <strong>Location:</strong> {{ $location->name }}
                </div>
                @if ($isSale)
                    <div class="status-badge {{ $isPaid ? 'status-paid' : 'status-unpaid' }}">
                        {{ $isPaid ? 'PAID' : 'UNPAID' }}
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Bill From / Bill To --}}
    <table class="addresses">
        <tr>
            <td>
                <div class="label">Bill From</div>
                <div class="name">{{ config('store.name') }}</div>
                <div class="detail">
                    {{ $location->address ?? config('store.address') }}<br>
                    {{ config('store.phone') }}
                </div>
            </td>
            <td>
                <div class="label">Bill To</div>
                @if ($isSale && $related->user)
                    <div class="name">{{ $related->user->name ?? 'Customer' }}</div>
                @elseif ($movement->buyer)
                    <div class="name">{{ $movement->buyer->name }}</div>
                    @if ($movement->buyer->phone)
                        <div class="detail">{{ $movement->buyer->phone }}</div>
                    @endif
                    @if ($movement->buyer->email)
                        <div class="detail">{{ $movement->buyer->email }}</div>
                    @endif
                @else
                    <div class="name">Walk-in Customer</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="num">#</th>
                <th>Description</th>
                <th class="qty">Qty</th>
                <th class="price">Unit Price</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @if ($isSale)
                @foreach ($related->items as $i => $item)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>{{ $item->product->name ?? 'Product #'.$item->product_id }}</td>
                    <td class="qty">{{ $item->qty }}</td>
                    <td class="price">Rp {{ number_format((float) $item->price, 0, ',', '.') }}</td>
                    <td class="amount">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td class="num">1</td>
                    <td>{{ $movement->product->name }}</td>
                    <td class="qty">{{ abs((int) $movement->quantity) }}</td>
                    <td class="price">Rp {{ number_format((float) ($movement->unit_price ?? 0), 0, ',', '.') }}</td>
                    <td class="amount">Rp {{ number_format(abs((int) $movement->quantity) * (float) ($movement->unit_price ?? 0), 0, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- Totals --}}
    @if ($isSale)
    <table class="totals-section">
        <tr>
            <td class="spacer"></td>
            <td class="totals-box">
                <table class="totals-table">
                    <tr><td>Subtotal</td><td>Rp {{ number_format((float) $related->total, 0, ',', '.') }}</td></tr>
                    @if ($related->paid_amount > 0)
                    <tr><td>Paid</td><td>Rp {{ number_format((float) $related->paid_amount, 0, ',', '.') }}</td></tr>
                        @if ($related->paid_amount > $related->total)
                        <tr><td>Change</td><td>Rp {{ number_format((float) ($related->paid_amount - $related->total), 0, ',', '.') }}</td></tr>
                        @endif
                    @endif
                    <tr class="total-row"><td>Total</td><td>Rp {{ number_format((float) $related->total, 0, ',', '.') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
    @endif

    {{-- Payment Info --}}
    @if ($isSale)
    <div class="payment-section">
        <div class="label">Payment Information</div>
        <table>
            <tr>
                <td>Payment Method</td>
                <td>: {{ match($related->payment_method ?? '') { 'cash' => 'Cash', 'transfer' => 'Bank Transfer', 'qris' => 'QRIS', default => ucfirst($related->payment_method ?? '-') } }}</td>
            </tr>
            <tr>
                <td>Invoice Date</td>
                <td>: {{ $movement->created_at->format('d M Y H:i') }}</td>
            </tr>
        </table>
    </div>
    @endif

    {{-- Notes --}}
    @if ($movement->notes)
    <div class="notes">
        <strong>Notes:</strong> {{ $movement->notes }}
    </div>
    @endif

    {{-- Signature --}}
    <table class="signature-section">
        <tr>
            <td>
                <div class="thanks">
                    Thank you for your business.<br>
                    <strong>{{ config('store.name') }}</strong>
                </div>
            </td>
            <td style="text-align: right;">
                <div class="sig-line">
                    {{ $movement->creator?->name ?? '-' }}<br>
                    <span style="font-size: 9px;">Authorized Signature</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        {{ config('store.name') }} &mdash; {{ $location->address ?? config('store.address') }} &mdash; {{ config('store.phone') }}
    </div>
</body>
</html>
