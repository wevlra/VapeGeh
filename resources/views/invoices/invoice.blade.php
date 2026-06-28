<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>
        @page { size: A4; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; line-height: 1.4; background: #fff; }

        .invoice-wrapper { padding: 0; }

        /* ── Header ────────────────────────────────────── */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .header-table td { vertical-align: top; padding: 0; }
        .logo-cell { width: 90px; text-align: center; padding-right: 16px; }
        .logo-cell img { width: 70px; height: auto; }
        .store-cell h1 { font-size: 20px; font-weight: 700; color: #e63946; margin-bottom: 3px; letter-spacing: 0.5px; }
        .store-cell p { font-size: 10px; color: #6c757d; line-height: 1.5; }
        .title-cell { text-align: right; vertical-align: middle; }
        .title-cell h2 { font-size: 28px; font-weight: 800; color: #e63946; letter-spacing: 3px; text-transform: uppercase; }

        /* ── Divider ───────────────────────────────────── */
        .divider { height: 3px; background: linear-gradient(to right, #e63946, #f4a261); margin: 0 0 20px 0; border: none; }

        /* ── Invoice Meta ──────────────────────────────── */
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .meta-table td { vertical-align: top; padding: 0; }
        .meta-left { width: 60%; }
        .meta-right { width: 40%; text-align: right; }
        .meta-label { font-size: 9px; color: #adb5bd; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
        .meta-value { font-size: 12px; color: #1a1a2e; font-weight: 600; margin-bottom: 6px; }

        /* ── Items Table ───────────────────────────────── */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table thead th {
            background: #1a1a2e;
            color: #ffffff;
            padding: 10px 12px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }
        .items-table thead th:first-child { border-radius: 4px 0 0 0; }
        .items-table thead th:last-child { border-radius: 0 4px 0 0; text-align: right; }
        .items-table thead th.text-center { text-align: center; }
        .items-table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 11px;
        }
        .items-table tbody tr:nth-child(even) { background: #f8f9fa; }
        .items-table tbody td:last-child { text-align: right; font-weight: 600; }
        .items-table tbody td.text-center { text-align: center; }
        .items-table tbody td:first-child { color: #6c757d; }

        /* ── Totals ────────────────────────────────────── */
        .totals-table { width: 260px; border-collapse: collapse; margin-left: auto; margin-bottom: 24px; }
        .totals-table td { padding: 5px 12px; font-size: 11px; }
        .totals-table td:first-child { color: #6c757d; }
        .totals-table td:last-child { text-align: right; font-weight: 600; }
        .totals-table .grand-total td {
            font-size: 14px;
            font-weight: 800;
            color: #e63946;
            border-top: 2px solid #1a1a2e;
            padding-top: 8px;
            padding-bottom: 8px;
        }
        .totals-table .paid td { color: #2d6a4f; }
        .totals-table .change td { color: #e63946; }

        /* ── Notes ─────────────────────────────────────── */
        .notes-box {
            background: #f8f9fa;
            border-left: 3px solid #e63946;
            padding: 10px 14px;
            margin-bottom: 24px;
            font-size: 10px;
            color: #495057;
        }
        .notes-box strong { color: #1a1a2e; }

        /* ── Signature ─────────────────────────────────── */
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 40px; }
        .signature-table td { vertical-align: bottom; padding: 0; width: 50%; }
        .sig-thanks { font-size: 11px; color: #6c757d; }
        .sig-store { font-size: 12px; font-weight: 700; color: #1a1a2e; margin-top: 4px; }
        .sig-line { width: 160px; border-top: 1px solid #1a1a2e; margin-top: 50px; padding-top: 4px; font-size: 10px; color: #495057; }

        /* ── Footer ────────────────────────────────────── */
        .footer { text-align: center; font-size: 9px; color: #adb5bd; margin-top: 30px; padding-top: 12px; border-top: 1px solid #e9ecef; }
    </style>
</head>
<body>
    @php
        $location = $movement->location;
        $related = $movement->related;
        $invoiceNumber = $related instanceof \App\Models\Sale ? $related->invoice_number : 'SM-'.$movement->id;
    @endphp

    <div class="invoice-wrapper">

        {{-- Header --}}
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <img src="{{ public_path('assets/images/logo-light-tr.png') }}" alt="{{ config('store.name') }}">
                </td>
                <td class="store-cell">
                    <h1>{{ config('store.name') }}</h1>
                    <p>{{ $location->address ?? config('store.address') }}</p>
                    <p>{{ config('store.phone') }}</p>
                </td>
                <td class="title-cell">
                    <h2>Invoice</h2>
                </td>
            </tr>
        </table>

        <hr class="divider">

        {{-- Meta --}}
        <table class="meta-table">
            <tr>
                <td class="meta-left">
                    <table style="border-collapse:collapse;">
                        <tr>
                            <td style="padding:0 24px 0 0;"><span class="meta-label">Invoice #</span><br><span class="meta-value">{{ $invoiceNumber }}</span></td>
                            <td style="padding:0 24px 0 0;"><span class="meta-label">Date</span><br><span class="meta-value">{{ $movement->created_at->format('d M Y') }}</span></td>
                            <td style="padding:0;"><span class="meta-label">Cashier</span><br><span class="meta-value">{{ $movement->creator?->name ?? '-' }}</span></td>
                        </tr>
                    </table>
                </td>
                <td class="meta-right">
                    <span class="meta-label">Location</span><br>
                    <span class="meta-value">{{ $location->name }}</span>
                    @if ($related instanceof \App\Models\Sale)
                    <br><span class="meta-label">Payment</span><br>
                    <span class="meta-value">{{ match($related->payment_method ?? '') { 'qris' => 'QRIS', 'cash' => 'Cash', 'transfer' => 'Transfer', default => ucfirst((string) ($related->payment_method ?? '-')) } }}</span>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Items --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:30px;">#</th>
                    <th>Product</th>
                    <th class="text-center" style="width:50px;">Qty</th>
                    <th style="width:100px; text-align:right;">Price</th>
                    <th style="width:110px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @if ($related instanceof \App\Models\Sale)
                    @foreach ($related->items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->product->name ?? 'Product #'.$item->product_id }}</td>
                        <td class="text-center">{{ $item->qty }}</td>
                        <td style="text-align:right;">Rp {{ number_format((float) $item->price, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td>1</td>
                        <td>{{ $movement->product->name }}</td>
                        <td class="text-center">{{ abs((int) $movement->quantity) }}</td>
                        <td style="text-align:right;">Rp {{ number_format((float) ($movement->unit_price ?? 0), 0, ',', '.') }}</td>
                        <td>Rp {{ number_format(abs((int) $movement->quantity) * (float) ($movement->unit_price ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- Totals --}}
        @if ($related instanceof \App\Models\Sale)
        <table class="totals-table">
            <tr><td>Subtotal</td><td>Rp {{ number_format((float) $related->total, 0, ',', '.') }}</td></tr>
            <tr class="grand-total"><td>Total</td><td>Rp {{ number_format((float) $related->total, 0, ',', '.') }}</td></tr>
            @if ($related->paid_amount > 0)
            <tr class="paid"><td>Paid</td><td>Rp {{ number_format((float) $related->paid_amount, 0, ',', '.') }}</td></tr>
                @if ($related->paid_amount > $related->total)
                <tr class="change"><td>Change</td><td>Rp {{ number_format((float) ($related->paid_amount - $related->total), 0, ',', '.') }}</td></tr>
                @endif
            @endif
        </table>
        @endif

        {{-- Notes --}}
        @if ($movement->notes)
        <div class="notes-box">
            <strong>Notes:</strong> {{ $movement->notes }}
        </div>
        @endif

        {{-- Signature --}}
        <table class="signature-table">
            <tr>
                <td>
                    <div class="sig-thanks">Thanks for your business,</div>
                    <div class="sig-store">{{ config('store.name') }}</div>
                </td>
                <td style="text-align:right;">
                    <div class="sig-thanks">Approved by,</div>
                    <div class="sig-line">{{ $movement->creator?->name ?? '-' }}</div>
                </td>
            </tr>
        </table>

        {{-- Footer --}}
        <div class="footer">
            {{ config('store.name') }} &middot; {{ $location->address ?? config('store.address') }} &middot; {{ config('store.phone') }}
        </div>

    </div>
</body>
</html>
