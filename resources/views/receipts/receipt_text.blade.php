@php
    $location = $movement->location;
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
    $padRight = function($str, $len) { return str_pad(substr($str, 0, $len), $len, " ", STR_PAD_RIGHT); };
    $padLeft = function($str, $len) { return str_pad(substr($str, 0, $len), $len, " ", STR_PAD_LEFT); };
@endphp
[C]<b>{{ config('store.name') }}</b>
@if(config('store.tagline'))
[C]{{ config('store.tagline') }}
@endif
[C]{{ $location->address ?? config('store.address') }}
[C]{{ config('store.phone') }}
--------------------------------
[L]No: {{ $refNumber }}
[L]Tipe: {{ $typeLabel }}
[L]Tgl: {{ $movement->created_at->format('d M Y H:i') }}
[L]Staf: {{ $movement->creator?->name ?? '-' }}
@if ($movement->buyer)
[L]Pembeli: {{ $movement->buyer->name }}
@endif
--------------------------------
@if ($related instanceof \App\Models\Sale)
@foreach ($related->items as $item)
[L]{{ $item->product->name ?? 'Produk #'.$item->product_id }}
[L]  {{ $item->qty }}x {{ number_format((float) $item->price, 0, ',', '.') }}[R]{{ number_format((float) $item->subtotal, 0, ',', '.') }}
@endforeach
--------------------------------
[L]Total:[R]Rp {{ number_format((float) $related->total, 0, ',', '.') }}
@if ($related->paid_amount > 0 && $related->paid_amount != $related->total)
[L]Bayar:[R]Rp {{ number_format((float) $related->paid_amount, 0, ',', '.') }}
[L]Kembali:[R]Rp {{ number_format((float) ($related->paid_amount - $related->total), 0, ',', '.') }}
@endif
[L]Metode:[R]{{ match($related->payment_method) { 'qris' => 'QRIS', default => ucfirst($related->payment_method) } }}
@elseif ($related instanceof \App\Models\StockEntry)
@foreach ($related->items as $item)
[L]{{ $item->product->name ?? 'Produk #'.$item->product_id }}
[L]  {{ $item->qty }}x {{ number_format((float) $item->unit_price, 0, ',', '.') }}
@endforeach
--------------------------------
[L]Total:[R]Rp {{ number_format($related->items->sum(fn ($i) => $i->qty * (float) $i->unit_price), 0, ',', '.') }}
@else
[L]{{ $movement->product->name }}
[L]  Jumlah:[R]{{ abs((int) $movement->quantity) }}
@endif
--------------------------------
[C]Terima Kasih!
