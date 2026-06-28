<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{ $invoice->name }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        * { font-family: "DejaVu Sans"; margin: 0; padding: 0; box-sizing: border-box; }
        body { font-size: 10px; color: #1e293b; line-height: 1.5; background: #fff; margin: 0; }

        .container { padding: 36pt; }

        /* ── Header ── */
        .header { width: 100%; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 3px solid #dc2626; }
        .header td { vertical-align: top; padding: 0; }
        .header .logo-cell { width: 50%; }
        .header .logo-cell img { max-height: 48px; margin-bottom: 6px; }
        .header .company-name { font-size: 18px; font-weight: bold; color: #0f172a; margin-bottom: 2px; }
        .header .company-detail { font-size: 10px; color: #64748b; line-height: 1.6; }
        .header .title-cell { width: 50%; text-align: right; }
        .header .invoice-title { font-size: 28px; font-weight: bold; color: #dc2626; letter-spacing: 3px; text-transform: uppercase; }
        .header .invoice-meta { font-size: 10px; color: #64748b; margin-top: 8px; line-height: 1.8; }
        .header .invoice-meta strong { color: #1e293b; }

        .status-badge {
            display: inline-block; padding: 3px 12px; border-radius: 4px;
            font-size: 10px; font-weight: bold; letter-spacing: 1px;
            text-transform: uppercase; margin-top: 6px;
        }
        .status-paid { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .status-unpaid { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }

        /* ── Bill From / Bill To ── */
        .addresses { width: 100%; margin-bottom: 24px; border-collapse: collapse; }
        .addresses td { width: 50%; vertical-align: top; padding: 16px 20px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .addresses td:first-child { border-right: none; border-radius: 6px 0 0 6px; }
        .addresses td:last-child { border-radius: 0 6px 6px 0; }
        .addresses .label { font-size: 9px; font-weight: bold; color: #dc2626; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px; }
        .addresses .name { font-size: 13px; font-weight: bold; color: #0f172a; margin-bottom: 4px; }
        .addresses .detail { font-size: 10px; color: #64748b; line-height: 1.7; }

        /* ── Items Table ── */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table thead th {
            background: #dc2626; color: #fff; padding: 10px 12px;
            font-size: 9px; font-weight: bold; text-transform: uppercase;
            letter-spacing: 1px; text-align: left;
        }
        .items-table thead th:first-child { border-radius: 6px 0 0 0; }
        .items-table thead th:last-child { border-radius: 0 6px 0 0; text-align: right; }
        .items-table thead th.num { text-align: center; width: 36px; }
        .items-table thead th.qty { text-align: center; width: 50px; }
        .items-table thead th.price { text-align: right; width: 110px; }
        .items-table thead th.amount { text-align: right; width: 120px; }
        .items-table tbody td {
            padding: 10px 12px; border-bottom: 1px solid #f1f5f9;
            font-size: 11px; color: #334155;
        }
        .items-table tbody td.num { text-align: center; color: #94a3b8; font-weight: bold; }
        .items-table tbody td.qty { text-align: center; }
        .items-table tbody td.price, .items-table tbody td.amount { text-align: right; }
        .items-table tbody tr:nth-child(even) td { background: #fef2f2; }
        .items-table tbody tr:last-child td { border-bottom: 2px solid #e2e8f0; }

        /* ── Totals ── */
        .totals-section { width: 100%; margin-bottom: 24px; border-collapse: collapse; }
        .totals-section td { padding: 0; vertical-align: top; }
        .totals-section .spacer { width: 55%; }
        .totals-section .totals-box { width: 45%; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 6px 12px; font-size: 11px; color: #64748b; }
        .totals-table td:last-child { text-align: right; font-weight: 500; color: #1e293b; }
        .totals-table .total-row td {
            padding: 10px 12px; font-size: 14px; font-weight: bold;
            color: #0f172a; border-top: 2px solid #dc2626; background: #fef2f2;
        }
        .totals-table .total-row td:last-child { color: #dc2626; }

        /* ── Payment Info ── */
        .payment-section {
            width: 100%; margin-bottom: 28px; padding: 16px 20px;
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;
        }
        .payment-section .label { font-size: 9px; font-weight: bold; color: #dc2626; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px; }
        .payment-section table { width: 100%; border-collapse: collapse; }
        .payment-section td { padding: 3px 0; font-size: 10px; color: #64748b; }
        .payment-section td:first-child { width: 120px; font-weight: 600; color: #475569; }

        /* ── Notes ── */
        .notes {
            font-size: 10px; color: #64748b; margin-bottom: 24px;
            padding: 12px 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px;
        }
        .notes strong { color: #991b1b; }

        /* ── Signature ── */
        .signature-section { width: 100%; margin-top: 32px; border-collapse: collapse; }
        .signature-section td { vertical-align: top; padding: 0; width: 50%; }
        .signature-section .thanks { font-size: 11px; color: #64748b; line-height: 1.6; }
        .signature-section .thanks strong { color: #0f172a; }
        .signature-section .sig-line { margin-top: 48px; width: 180px; border-top: 1px solid #cbd5e1; padding-top: 6px; font-size: 10px; color: #94a3b8; text-align: center; }

        /* ── Footer ── */
        .footer {
            margin-top: 32px; padding-top: 12px; border-top: 1px solid #e2e8f0;
            text-align: center; font-size: 9px; color: #94a3b8;
        }

        .cool-gray { color: #64748b; }
        .border-0 { border: none !important; }
        .pl-0 { padding-left: 0 !important; }
        .pr-0 { padding-right: 0 !important; }
        .px-0 { padding-left: 0 !important; padding-right: 0 !important; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
    </style>
</head>
<body>
<div class="container">
    {{-- Header --}}
    <table class="header">
        <tr>
            <td class="logo-cell">
                @if($invoice->logo)
                    <img src="{{ $invoice->getLogo() }}" alt="{{ config('store.name') }}">
                @endif
                <div class="company-name">{{ $invoice->seller->name }}</div>
                <div class="company-detail">
                    @if($invoice->seller->address){{ $invoice->seller->address }}<br>@endif
                    @if($invoice->seller->phone){{ $invoice->seller->phone }}@endif
                    @foreach($invoice->seller->custom_fields as $key => $value)
                        {{ $value }}
                    @endforeach
                </div>
            </td>
            <td class="title-cell">
                <div class="invoice-title">Invoice</div>
                <div class="invoice-meta">
                    <strong>Invoice #:</strong> {{ $invoice->getSerialNumber() }}<br>
                    <strong>Date:</strong> {{ $invoice->getDate() }}<br>
                    @if($invoice->getPayUntilDate())
                        <strong>Due:</strong> {{ $invoice->getPayUntilDate() }}<br>
                    @endif
                    @if($invoice->buyer->name)
                        <strong>To:</strong> {{ $invoice->buyer->name }}<br>
                    @endif
                </div>
                @if($invoice->status)
                    <div class="status-badge {{ strtolower($invoice->status) === 'paid' ? 'status-paid' : 'status-unpaid' }}">
                        {{ $invoice->status }}
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
                <div class="name">{{ $invoice->seller->name }}</div>
                <div class="detail">
                    @if($invoice->seller->address){{ $invoice->seller->address }}<br>@endif
                    @if($invoice->seller->phone){{ $invoice->seller->phone }}<br>@endif
                    @foreach($invoice->seller->custom_fields as $key => $value)
                        {{ ucfirst($key) }}: {{ $value }}<br>
                    @endforeach
                </div>
            </td>
            <td>
                <div class="label">Bill To</div>
                <div class="name">{{ $invoice->buyer->name ?? 'Walk-in Customer' }}</div>
                <div class="detail">
                    @if($invoice->buyer->address){{ $invoice->buyer->address }}<br>@endif
                    @if($invoice->buyer->phone){{ $invoice->buyer->phone }}<br>@endif
                    @foreach($invoice->buyer->custom_fields as $key => $value)
                        {{ ucfirst($key) }}: {{ $value }}<br>
                    @endforeach
                </div>
            </td>
        </tr>
    </table>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="num">#</th>
                <th>Description</th>
                @if($invoice->hasItemUnits)
                    <th class="qty">Unit</th>
                @endif
                <th class="qty">Qty</th>
                <th class="price">Unit Price</th>
                @if($invoice->hasItemDiscount)
                    <th class="price">Discount</th>
                @endif
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $i => $item)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>
                    {{ $item->title }}
                    @if($item->description)
                        <p class="cool-gray" style="font-size:9px; margin-top:2px;">{{ $item->description }}</p>
                    @endif
                </td>
                @if($invoice->hasItemUnits)
                    <td class="qty">{{ $item->units }}</td>
                @endif
                <td class="qty">{{ $item->quantity }}</td>
                <td class="price">{{ $invoice->formatCurrency($item->price_per_unit) }}</td>
                @if($invoice->hasItemDiscount)
                    <td class="price">{{ $invoice->formatCurrency($item->discount) }}</td>
                @endif
                <td class="amount">{{ $invoice->formatCurrency($item->sub_total_price) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="totals-section">
        <tr>
            <td class="spacer"></td>
            <td class="totals-box">
                <table class="totals-table">
                    @if($invoice->hasItemOrInvoiceDiscount())
                        <tr><td>Discount</td><td>{{ $invoice->formatCurrency($invoice->total_discount) }}</td></tr>
                    @endif
                    @if($invoice->hasItemOrInvoiceTax())
                        <tr><td>Tax</td><td>{{ $invoice->formatCurrency($invoice->total_taxes) }}</td></tr>
                    @endif
                    @if($invoice->shipping_amount)
                        <tr><td>Shipping</td><td>{{ $invoice->formatCurrency($invoice->shipping_amount) }}</td></tr>
                    @endif
                    <tr class="total-row"><td>Total</td><td>{{ $invoice->formatCurrency($invoice->total_amount) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Payment Info --}}
    <div class="payment-section">
        <div class="label">Payment Information</div>
        <table>
            <tr><td>Payment Method</td><td>: {{ $invoice->buyer->custom_fields['payment_method'] ?? '-' }}</td></tr>
            <tr><td>Invoice Date</td><td>: {{ $invoice->getDate() }}</td></tr>
            @if($invoice->getPayUntilDate())
                <tr><td>Due Date</td><td>: {{ $invoice->getPayUntilDate() }}</td></tr>
            @endif
        </table>
    </div>

    {{-- Notes --}}
    @if($invoice->notes)
    <div class="notes">
        <strong>Notes:</strong> {{ $invoice->notes }}
    </div>
    @endif

    {{-- Signature --}}
    <table class="signature-section">
        <tr>
            <td>
                <div class="thanks">
                    Thank you for your business.<br>
                    <strong>{{ $invoice->seller->name }}</strong>
                </div>
            </td>
            <td style="text-align: right;">
                <div class="sig-line">
                    {{ $invoice->seller->name }}<br>
                    <span style="font-size: 9px;">Authorized Signature</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        {{ $invoice->seller->name }} — {{ $invoice->seller->address ?? '' }} — {{ $invoice->seller->phone ?? '' }}
    </div>
</div>

<script type="text/php">
    if (isset($pdf) && $PAGE_COUNT > 1) {
        $text = "Page {PAGE_NUM} / {PAGE_COUNT}";
        $size = 9;
        $font = $fontMetrics->getFont("Verdana");
        $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
        $x = ($pdf->get_width() - $width);
        $y = $pdf->get_height() - 35;
        $pdf->page_text($x, $y, $text, $font, $size);
    }
</script>
</body>
</html>
