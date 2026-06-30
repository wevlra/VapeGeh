<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; padding: 8px 12px; color: #000; }
        .header { text-align: center; margin-bottom: 12px; }
        .header img { max-height: 64px; }
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
        <img src="{{ asset('assets/images/logo-stacked-light-tr.png') }}" alt="{{ config('store.name') }}">
        {{-- <div class="name">{{ config('store.name') }}</div> --}}
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
            <thead><tr><th>Item</th><th>Jumlah</th><th>Harga</th><th>Subtotal</th></tr></thead>
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
            <thead><tr><th>Item</th><th>Jumlah</th></tr></thead>
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
            Dari: {{ $related->fromLocation?->name }}<br>
            Ke: {{ $related->toLocation?->name }}
        </div>
    @elseif ($related instanceof \App\Models\StockEntry)
        @php $stockIn = $related->type === 'in'; @endphp
        <table>
            <thead><tr><th>Item</th><th>Jumlah</th><th>Harga</th></tr></thead>
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
    @else
        <table>
            <thead><tr><th>Item</th><th>Jumlah</th></tr></thead>
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
        Terima kasih!<br>
        Barang yang sudah dibeli tidak dapat dikembalikan.
    </div>

    <script>
        function goPrint() { window.print(); }
        window.onafterprint = function() { window.close(); }

        window.onload = function() {
            if (window.Capacitor && window.Capacitor.isNativePlatform()) {
                showPrinterModal();
            } else {
                goPrint();
            }
        }

        // --- Printer modal (Capacitor only) ---
        function showPrinterModal() {
            var modal = document.getElementById('printer-modal');
            if (modal) modal.style.display = 'flex';
            loadDevices();
        }

        function hidePrinterModal() {
            var modal = document.getElementById('printer-modal');
            if (modal) modal.style.display = 'none';
        }

        async function loadDevices() {
            var list = document.getElementById('printer-list');
            var status = document.getElementById('printer-status');
            var noBluetooth = document.getElementById('no-bluetooth');
            var noDevices = document.getElementById('no-devices');
            list.innerHTML = '';

            try {
                var r = await window.Capacitor.Plugins.Printer.getPairedDevices();
                noBluetooth.style.display = 'none';
                if (r && r.devices && r.devices.length > 0) {
                    noDevices.style.display = 'none';
                    r.devices.forEach(function(d) {
                        var div = document.createElement('div');
                        div.className = 'printer-item';
                        div.innerHTML = '<strong>' + d.name + '</strong> <span class="addr">' + d.address + '</span>';
                        div.onclick = function() { selectDevice(d); };
                        list.appendChild(div);
                    });

                    // mark currently selected
                    try {
                        var sel = await window.Capacitor.Plugins.Printer.getSelectedPrinter();
                        if (sel && sel.address) {
                            document.querySelectorAll('.printer-item').forEach(function(el) {
                                if (el.innerHTML.indexOf(sel.address) > -1) {
                                    el.classList.add('selected');
                                }
                            });
                        }
                    } catch(e) {}
                } else {
                    noDevices.style.display = 'block';
                }
            } catch (e) {
                if (e.message && e.message.indexOf('disabled') > -1) {
                    noBluetooth.style.display = 'block';
                }
            }
            status.style.display = 'none';
        }

        async function selectDevice(device) {
            try {
                await window.Capacitor.Plugins.Printer.selectPrinter({ address: device.address });
                document.querySelectorAll('.printer-item').forEach(function(el) { el.classList.remove('selected'); });
                event.currentTarget.classList.add('selected');
            } catch(e) { alert('Gagal memilih printer: ' + e.message); }
        }

        async function doPrint() {
            var status = document.getElementById('printer-status');
            status.style.display = 'block';
            status.textContent = 'Mencetak...';
            try {
                var url = window.location.pathname + '/print-data';
                var r = await fetch(url);
                var data = await r.json();
                await window.Capacitor.Plugins.Printer.printReceipt({ payload: data });
                status.textContent = 'Nota berhasil dicetak!';
                setTimeout(hidePrinterModal, 1500);
            } catch(e) {
                status.textContent = 'Gagal: ' + (e.message || 'unknown');
            }
        }
    </script>

    <style>
        #printer-modal {
            display: none;
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center; justify-content: center;
            font-family: 'Courier New', monospace;
        }
        #printer-modal .modal-box {
            background: #fff;
            width: 90%; max-width: 360px;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.2);
        }
        #printer-modal h3 { margin: 0 0 4px; font-size: 16px; }
        #printer-modal .sub { font-size: 12px; color: #666; margin-bottom: 16px; }
        #printer-modal .printer-item {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 6px;
            cursor: pointer;
            font-size: 12px;
        }
        #printer-modal .printer-item:hover { background: #f5f5f5; }
        #printer-modal .printer-item.selected { border-color: #b8860b; background: #fffbee; }
        #printer-modal .printer-item .addr { font-size: 11px; color: #999; display: block; }
        #printer-modal .btn-row { display: flex; gap: 8px; margin-top: 12px; }
        #printer-modal .btn-row button {
            flex: 1; padding: 8px; border: none; border-radius: 6px;
            font-size: 13px; font-weight: 600; cursor: pointer;
        }
        #printer-modal .btn-print { background: #b8860b; color: #fff; }
        #printer-modal .btn-cancel { background: #e5e7eb; color: #333; }
        #printer-modal .btn-refresh { background: #f3f4f6; color: #555; padding: 6px 12px; font-size: 11px; }
        #printer-modal #printer-status { margin-top: 8px; font-size: 12px; }
        #no-bluetooth, #no-devices { display: none; font-size: 12px; color: #b91c1c; margin: 8px 0; }
    </style>

    <div id="printer-modal">
        <div class="modal-box">
            <h3>Cetak Nota</h3>
            <p class="sub">Pilih printer Bluetooth</p>
            <div id="no-bluetooth">Bluetooth tidak aktif. Aktifkan di Pengaturan Android.</div>
            <div id="no-devices">Tidak ada printer terpair. Pair dulu di Pengaturan Bluetooth.</div>
            <div id="printer-list"></div>
            <div id="printer-status" style="display:none;"></div>
            <div class="btn-row">
                <button class="btn-cancel" onclick="hidePrinterModal()">Batal</button>
                <button class="btn-refresh" onclick="loadDevices()">Refresh</button>
                <button class="btn-print" onclick="doPrint()">Cetak</button>
            </div>
        </div>
    </div>
</body>
</html>
